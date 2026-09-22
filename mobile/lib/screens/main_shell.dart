import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/screens/attendance/attendance_screen.dart';
import 'package:libcontrol_app/screens/home/home_screen.dart';
import 'package:libcontrol_app/screens/profile/profile_screen.dart';
import 'package:libcontrol_app/screens/scan/scan_screen.dart';
import 'package:libcontrol_app/screens/seats/seats_screen.dart';
import 'package:libcontrol_app/widgets/app_drawer.dart';
import 'package:libcontrol_app/widgets/custom_bottom_navigation.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _currentIndex = 0;
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  void _onDrawerNavigate(int index) {
    Navigator.of(context).pop();
    setState(() => _currentIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      HomeScreen(
        onOpenDrawer: () => _scaffoldKey.currentState?.openDrawer(),
        onNavigateTab: (index) => setState(() => _currentIndex = index),
      ),
      AttendanceScreen(
        onBack: () => setState(() => _currentIndex = 0),
      ),
      ScanScreen(
        onBack: () => setState(() => _currentIndex = 0),
      ),
      SeatsScreen(
        onViewQr: () => setState(() => _currentIndex = 2),
        onBack: () => setState(() => _currentIndex = 0),
      ),
      ProfileScreen(
        onBack: () => setState(() => _currentIndex = 0),
      ),
    ];

    return Scaffold(
      key: _scaffoldKey,
      drawer: AppDrawer(
        currentIndex: _currentIndex,
        onNavigate: _onDrawerNavigate,
        onNotifications: () {
          Navigator.of(context).pop();
          Navigator.of(context).pushNamed(AppRoutes.notifications);
        },
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: CustomBottomNavigation(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
      ),
    );
  }
}
