import 'package:flutter/material.dart';

class LibControlLogo extends StatelessWidget {
  const LibControlLogo({
    super.key,
    this.height = 56,
    this.wide = false,
  });

  /// Height of the logo (width scales for wide landscape asset).
  final double height;

  /// Use landscape wordmark (connect / marketing screens).
  final bool wide;

  @override
  Widget build(BuildContext context) {
    if (wide) {
      return Image.asset(
        'assets/brand/lc-logo-landscape.png',
        height: height,
        fit: BoxFit.contain,
      );
    }

    return Image.asset(
      'assets/brand/yellow-lc-logo.png',
      height: height,
      width: height,
      fit: BoxFit.contain,
    );
  }
}
