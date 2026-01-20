import 'package:flutter/material.dart';

const double designWidth = 375.0;
const double designHeight = 812.0;

extension ResponsiveSize on num {
  double w(BuildContext context) {
    final double screenWidth = MediaQuery.sizeOf(context).width;
    return (this / designWidth) * screenWidth;
  }

  double h(BuildContext context) {
    final double screenHeight = MediaQuery.sizeOf(context).height;
    return (this / designHeight) * screenHeight;
  }

  double sp(BuildContext context) {
    final double screenWidth = MediaQuery.sizeOf(context).width;
    return (this / designWidth) * screenWidth;
  }
}