import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/models/seat.dart';
import 'package:libcontrol_app/widgets/app_header.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';
import 'package:libcontrol_app/widgets/seat_tile.dart';

class SeatsScreen extends StatefulWidget {
  const SeatsScreen({super.key});

  @override
  State<SeatsScreen> createState() => _SeatsScreenState();
}

class _SeatsScreenState extends State<SeatsScreen> {
  int _tabIndex = 0;
  String _selectedHall = DummyData.halls.first;
  String? _selectedSeatCode;

  List<SeatItem> get _seats {
    return DummyData.seatsForHall(_selectedHall).map((seat) {
      if (seat.code == _selectedSeatCode) {
        return SeatItem(code: seat.code, status: SeatStatus.selected);
      }
      return seat;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const AppHeader(title: 'Book a Seat'),
                  const SizedBox(height: 20),
                  SegmentedButton<int>(
                    segments: const [
                      ButtonSegment(value: 0, label: Text('Available')),
                      ButtonSegment(value: 1, label: Text('My Bookings')),
                    ],
                    selected: {_tabIndex},
                    onSelectionChanged: (value) => setState(() => _tabIndex = value.first),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _selectedHall,
                    decoration: const InputDecoration(labelText: 'Select hall'),
                    items: DummyData.halls
                        .map((hall) => DropdownMenuItem(value: hall, child: Text(hall)))
                        .toList(),
                    onChanged: (value) {
                      if (value == null) return;
                      setState(() {
                        _selectedHall = value;
                        _selectedSeatCode = null;
                      });
                    },
                  ),
                  const SizedBox(height: 16),
                  const Wrap(
                    spacing: 16,
                    runSpacing: 8,
                    children: [
                      _LegendItem(color: AppColors.successBg, label: 'Available'),
                      _LegendItem(color: AppColors.dangerBg, label: 'Occupied'),
                      _LegendItem(color: AppColors.primary, label: 'Selected'),
                    ],
                  ),
                  const SizedBox(height: 16),
                  LayoutBuilder(
                    builder: (context, constraints) {
                      final crossAxisCount = constraints.maxWidth > 360 ? 5 : 4;

                      return GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: crossAxisCount,
                          mainAxisSpacing: 10,
                          crossAxisSpacing: 10,
                          childAspectRatio: 1,
                        ),
                        itemCount: _seats.length,
                        itemBuilder: (context, index) {
                          final seat = _seats[index];
                          return SeatTile(
                            seat: seat,
                            onTap: () => setState(() => _selectedSeatCode = seat.code),
                          );
                        },
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
          Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            decoration: const BoxDecoration(
              color: AppColors.white,
              border: Border(top: BorderSide(color: AppColors.border)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _selectedSeatCode != null ? 'Seat $_selectedSeatCode' : 'Select a seat',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 4),
                Text(_selectedHall, style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 12),
                PrimaryButton(
                  label: 'Confirm Booking',
                  onPressed: _selectedSeatCode == null ? null : () {},
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LegendItem extends StatelessWidget {
  const _LegendItem({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: AppColors.border),
          ),
        ),
        const SizedBox(width: 6),
        Text(label, style: Theme.of(context).textTheme.bodySmall),
      ],
    );
  }
}
