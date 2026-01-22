import 'package:flutter/material.dart';
import 'my_claimed_offers.dart';
import 'my_help_requests.dart';
import 'notification_screen.dart';
import 'package:part_1/screens/offers/my_offers_screen.dart';
import 'package:part_1/screens/requests/my_help_responses.dart';

class MyItemsScreen extends StatelessWidget {
  const MyItemsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    final text = theme.textTheme;

    return Scaffold(
      backgroundColor: colors.surface,

      // =========================
      // APP BAR
      // =========================
      appBar: AppBar(
        title: const Text('My Items'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const NotificationScreen(),
                ),
              );
            },
          ),
        ],
      ),

      // =========================
      // BODY
      // =========================
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const SizedBox(height: 8),

          // =========================
          // CREATED BY ME
          // =========================
          Text(
            'Created by Me',
            style: text.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),

          _itemCard(
            context,
            icon: Icons.volunteer_activism_outlined,
            title: 'My Offers',
            desc: 'Manage the offers you’ve created.',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const MyOffersScreen(),
                ),
              );
            },
          ),

          const SizedBox(height: 14),

          _itemCard(
            context,
            icon: Icons.help_outline,
            title: 'My Requests',
            desc: 'Track and manage your submitted requests.',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const MyHelpRequestsScreen(),
                ),
              );
            },
          ),

          const SizedBox(height: 28),
          Divider(color: colors.outlineVariant),
          const SizedBox(height: 18),

          // =========================
          // CLAIMED BY ME
          // =========================
          Text(
            'Claimed by Me',
            style: text.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),

          _itemCard(
            context,
            icon: Icons.favorite_border,
            title: 'My Claimed Offers',
            desc: 'View all offers you have claimed so far.',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const MyClaimedOffersScreen(),
                ),
              );
            },
          ),

          const SizedBox(height: 14),

          _itemCard(
            context,
            icon: Icons.handshake_outlined,
            title: 'My Claimed Requests',
            desc: 'View all requests you’ve helped to fulfill.',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const MyHelpResponsesScreen(),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // =========================
  // ITEM CARD (REUSABLE)
  // =========================
  Widget _itemCard(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String desc,
    required VoidCallback onTap,
  }) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    final text = theme.textTheme;

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Row(
            children: [
              Container(
                height: 48,
                width: 48,
                decoration: BoxDecoration(
                  color: colors.primary,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  icon,
                  color: colors.onPrimary,
                  size: 22,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: text.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      desc,
                      style: text.bodySmall,
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: colors.onSurfaceVariant,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
