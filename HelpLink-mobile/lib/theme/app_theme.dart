import 'package:flutter/material.dart';
import 'app_colors.dart';

class AppTheme {
  static ThemeData lightTheme = ThemeData(
    useMaterial3: true,

    // =========================
    // BACKGROUND
    // =========================
    scaffoldBackgroundColor: AppColors.background,

    // =========================
    // COLOR SCHEME (IMPORTANT)
    // =========================
    colorScheme: const ColorScheme.light(
      primary: AppColors.primary,
      secondary: AppColors.secondary,

      background: AppColors.background,
      surface: AppColors.surface,
      surfaceVariant: AppColors.accent,

      onPrimary: Colors.white,
      onSecondary: AppColors.textPrimary,
      onBackground: AppColors.textPrimary,
      onSurface: AppColors.textPrimary,
      onSurfaceVariant: AppColors.textSecondary,

      error: AppColors.error,
      onError: Colors.white,
    ),

    // =========================
    // APP BAR
    // =========================
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      elevation: 0,
      centerTitle: true,
      titleTextStyle: TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.bold,
        color: Colors.white,
        letterSpacing: 0.2,
      ),
    ),

    // =========================
    // CARD
    // =========================
    cardTheme: CardThemeData(
      color: AppColors.card,
      elevation: 4, // IMPORTANT: bagi depth
      shadowColor: Colors.black12,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
      ),
      margin: EdgeInsets.zero,
    ),

    // =========================
    // TEXT
    // =========================
    textTheme: const TextTheme(
      headlineSmall: TextStyle(
        fontSize: 24,
        fontWeight: FontWeight.bold,
        color: AppColors.textPrimary,
        letterSpacing: -0.2,
      ),
      titleMedium: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      ),
      bodyMedium: TextStyle(
        fontSize: 14,
        height: 1.4,
        color: AppColors.textSecondary,
      ),
      bodySmall: TextStyle(
        fontSize: 12,
        height: 1.3,
        color: AppColors.textMuted,
      ),
    ),

    // =========================
    // ICON
    // =========================
    iconTheme: const IconThemeData(
      color: AppColors.primary,
      size: 24,
    ),

    // =========================
    // DIVIDER
    // =========================
    dividerTheme: const DividerThemeData(
      color: Color(0xFFE2E6E4),
      thickness: 1,
    ),
  );
}
