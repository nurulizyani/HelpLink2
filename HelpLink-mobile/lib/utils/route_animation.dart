import 'package:flutter/material.dart';

Route smoothRoute(Widget page, {bool fromRight = true}) {
  return PageRouteBuilder(
    pageBuilder: (_, animation, __) => page,
    transitionsBuilder: (_, animation, __, child) {
      final begin = fromRight ? const Offset(0.2, 0) : const Offset(-0.2, 0);
      const end = Offset.zero;
      const curve = Curves.easeInOut;

      final slideTween =
          Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
      final fadeTween = Tween<double>(begin: 0, end: 1);

      return SlideTransition(
        position: animation.drive(slideTween),
        child: FadeTransition(
          opacity: animation.drive(fadeTween),
          child: child,
        ),
      );
    },
    transitionDuration: const Duration(milliseconds: 400),
  );
}
