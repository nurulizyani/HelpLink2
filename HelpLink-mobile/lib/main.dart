import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_auth/firebase_auth.dart' as fb;

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'config/api_config.dart';
import 'firebase_options.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

// Notifications
import 'services/notification_service.dart';

// Theme
import 'theme/app_theme.dart';

// Screens
import 'screens/login_screen.dart';
import 'screens/verify_email_screen.dart';
import 'app_shell.dart';

// Chat
import 'screens/chat/chat_room_screen.dart';

// Offers
import 'screens/my_claimed_offers.dart';

// Requests
import 'screens/requests/help_requests_list.dart';
import 'screens/requests/help_request_detail.dart';
import 'screens/requests/help_request_create.dart';
import 'screens/my_help_requests.dart';
import 'screens/requests/my_help_responses.dart';
import 'screens/requests/help_response_detail.dart';

// ===============================
// GLOBAL NAVIGATOR
// ===============================
final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // ================= FIREBASE =================
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  // ================= SUPABASE =================
  await Supabase.initialize(
    url: 'https://pbvktqnklxiwkbxetycl.supabase.co',
    anonKey: 'sb_publishable_LD_8iJxJ4ZuDypP8hu1Bng_Gn2JG8ZZ',
  );

  // ================= FCM =================
  FirebaseMessaging.onBackgroundMessage(
    _firebaseMessagingBackgroundHandler,
  );

  await NotificationService.initialize();

  runApp(const HelpLinkApp());
}


// ===============================
// BACKGROUND NOTIFICATION
// ===============================
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  await NotificationService.showNotification(message);
}

// ===============================
// APP ROOT
// ===============================
class HelpLinkApp extends StatelessWidget {
  const HelpLinkApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      debugShowCheckedModeBanner: false,
      title: 'HelpLink',
      theme: AppTheme.lightTheme,
      home: const AuthWrapper(),
      routes: {
        '/myClaimOffers': (_) => const MyClaimedOffersScreen(),
        '/helpRequests': (_) => const HelpRequestsListScreen(),
        '/helpRequestCreate': (_) => const HelpRequestCreateScreen(),
        '/myHelpRequests': (_) => const MyHelpRequestsScreen(),
        '/myHelpResponses': (_) => const MyHelpResponsesScreen(),
      },
      onGenerateRoute: (settings) {
        if (settings.name == '/helpRequestDetail') {
          final args = settings.arguments as Map<String, dynamic>;
          return MaterialPageRoute(
            builder: (_) => HelpRequestDetailScreen(request: args),
          );
        }

        if (settings.name == '/helpResponseDetail') {
          final args = settings.arguments as Map<String, dynamic>;
          return MaterialPageRoute(
            builder: (_) => HelpResponseDetailScreen(claim: args),
          );
        }

        if (settings.name == '/chatRoom') {
          final args = settings.arguments as Map<String, dynamic>;
          final conversationId = args['conversationId'] as int;
          final otherUserName = (args['otherUserName'] ?? 'Chat').toString();

          return MaterialPageRoute(
            builder: (_) => ChatRoomScreen(
              conversationId: conversationId,
              otherUserName: otherUserName,
            ),
          );
        }

        return null;
      },
    );
  }
}

// ===============================
// AUTH WRAPPER + FCM INIT
// ===============================
class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  StreamSubscription<String>? _tokenRefreshSub;
  StreamSubscription<RemoteMessage>? _onMessageSub;
  StreamSubscription<RemoteMessage>? _onMessageOpenedSub;

  bool _fcmInitialized = false;

  @override
  void initState() {
    super.initState();
    _initFCM();
  }

  @override
  void dispose() {
    _tokenRefreshSub?.cancel();
    _onMessageSub?.cancel();
    _onMessageOpenedSub?.cancel();
    super.dispose();
  }

  // ===============================
  // INIT FCM (SAFE)
  // ===============================
  Future<void> _initFCM() async {
    if (_fcmInitialized) return;
    _fcmInitialized = true;

    final messaging = FirebaseMessaging.instance;

    await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Foreground message
    _onMessageSub = FirebaseMessaging.onMessage.listen(
      (RemoteMessage message) async {
        await NotificationService.showNotification(message);
      },
    );

    // User taps notification (background)
    _onMessageOpenedSub =
        FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _handlePushNavigation(message);
    });

    // User taps notification when app is terminated
    final initialMessage = await messaging.getInitialMessage();
    if (initialMessage != null) {
      _handlePushNavigation(initialMessage);
    }

    // Initial token
    final token = await messaging.getToken();
    if (token != null && token.isNotEmpty) {
      await _trySendTokenToServer(token);
    }

    // Refresh token
    _tokenRefreshSub = messaging.onTokenRefresh.listen((newToken) {
      _trySendTokenToServer(newToken);
    });
  }

  void _handlePushNavigation(RemoteMessage message) {
    final data = message.data;

    final conversationIdStr = data['conversation_id']?.toString();
    final conversationId = int.tryParse(conversationIdStr ?? '');

    if (conversationId != null) {
      final otherUserName =
          (data['title'] ?? data['other_user_name'] ?? 'Chat').toString();

      navigatorKey.currentState?.pushNamed(
        '/chatRoom',
        arguments: {
          'conversationId': conversationId,
          'otherUserName': otherUserName,
        },
      );
    }
  }

  // ===============================
  // SEND TOKEN TO BACKEND (SAFE + RETRY)
  // ===============================
  Future<void> _trySendTokenToServer(String fcmToken) async {
    final prefs = await SharedPreferences.getInstance();
    final authToken = prefs.getString('auth_token');

    if (authToken == null || authToken.isEmpty) return;

    final lastSent = prefs.getString('last_fcm_token_sent');
    if (lastSent == fcmToken) return;

    try {
      final res = await http.post(
        Uri.parse('${ApiConfig.apiBase}/save-fcm-token'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'fcm_token': fcmToken}),
      );

      if (res.statusCode == 200 || res.statusCode == 201) {
        await prefs.setString('last_fcm_token_sent', fcmToken);
      }
    } catch (_) {}
  }

  // ===============================
  // AUTH FLOW
  // ===============================
  @override
Widget build(BuildContext context) {
  return StreamBuilder<fb.User?>(
    stream: fb.FirebaseAuth.instance.authStateChanges(),
    builder: (context, snapshot) {
      if (snapshot.connectionState == ConnectionState.waiting) {
        return const Scaffold(
          body: Center(child: CircularProgressIndicator()),
        );
      }

      if (snapshot.hasData && snapshot.data != null) {
        final user = snapshot.data!;

        if (!user.emailVerified) {
          return VerifyEmailSentScreen(
            email: user.email ?? '',
          );
        }

        return const AppShell();
      }

      return const LoginScreen();
    },
  );
}

  }

