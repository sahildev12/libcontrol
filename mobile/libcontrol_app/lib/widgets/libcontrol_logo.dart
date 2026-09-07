import 'package:flutter/material.dart';

class LibControlLogo extends StatelessWidget {
  const LibControlLogo({super.key, this.size = 72});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/brand/only-logo-main-color.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );
  }
}
