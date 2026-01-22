// lib/app_shell.dart
import 'package:flutter/material.dart';
import 'screens/dashboard_screen.dart';
import 'screens/chat/chat_list_screen.dart';
import 'screens/my_items_screen.dart';
import 'screens/profile_screen.dart';

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    DashboardScreen(),
    ChatListScreen(),
    MyItemsScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: _screens[_currentIndex],
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.08),
              blurRadius: 12,
              offset: const Offset(0, -3),
            ),
          ],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) => setState(() => _currentIndex = index),
          type: BottomNavigationBarType.fixed,
          backgroundColor: theme.colorScheme.surface,
          elevation: 0,

          // 🔥 USE THEME COLORS
          selectedItemColor: theme.colorScheme.primary,
          unselectedItemColor: theme.colorScheme.onSurface.withOpacity(0.5),

          selectedLabelStyle: const TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 12.5,
          ),
          unselectedLabelStyle: const TextStyle(
            fontWeight: FontWeight.w500,
            fontSize: 12,
          ),

          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.home_outlined),
              activeIcon: Icon(Icons.home_rounded),
              label: 'Home',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.chat_bubble_outline),
              activeIcon: Icon(Icons.chat_bubble),
              label: 'Messages',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.volunteer_activism_outlined),
              activeIcon: Icon(Icons.volunteer_activism),
              label: 'My Items',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person),
              label: 'Profile',
            ),
          ],
        ),
      ),
    );
  }
}
