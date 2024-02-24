@php
$uniqueDates = array_unique($schedules->pluck('date')->toArray());
sort($uniqueDates);

$uniqueBatchFacultiesMorning = [];
$uniqueBatchFacultiesAfternoon = [];
$uniqueBatchSlotTimesMorning = [];
$uniqueBatchSlotTimesAfternoon = [];

$colorIndex = 0;

foreach ($schedules as $schedule) {
    $batchCode = '';
    $batch = App\Models\Batch::find($schedule->batch_id);

    if ($batch) {
        $batchCode = $batch->batch_code;
    }

    $date = $schedule->date;
    $originalSlotTime = $schedule->original_slot_time;
    list($startTime, $endTime) = explode('-', $originalSlotTime);

    $startTimeObj = new DateTime($startTime);
    $endTimeObj = new DateTime($endTime);

    $formattedStartTime = $startTimeObj->format('h:i A');
    $formattedEndTime = $endTimeObj->format('h:i A');

    $formattedOriginalSlotTime = $formattedStartTime . '-' . $formattedEndTime;
    $hour = $startTimeObj->format('H');

    if ($hour >= 12 && $hour < 24) {
        // Afternoon schedule
        $uniqueBatchFacultiesAfternoon[$batchCode][$date][$formattedOriginalSlotTime][] = $schedule;
        $uniqueBatchSlotTimesAfternoon[$date][$formattedOriginalSlotTime] = true;
    } else {
        // Morning schedule
        $uniqueBatchFacultiesMorning[$batchCode][$date][$formattedOriginalSlotTime][] = $schedule;
        $uniqueBatchSlotTimesMorning[$date][$formattedOriginalSlotTime] = true;
    }

    // Check for uniqueness and add to $uniqueDates with three empty time slots if not unique
    $dateCount = count(array_keys($schedules->pluck('date')->toArray(), $date));
    if ($dateCount > 1 && !in_array($date, $uniqueDates)) {
        $uniqueDates[] = $date;
        $uniqueBatchSlotTimesMorning[$date] = array_fill(0, 3, true);
    }
}

$startDate = new DateTime($schedules->min('date'));
$endDate = new DateTime($schedules->max('date'));

$allDatesInRange = [];

while ($startDate <= $endDate) {
    $allDatesInRange[] = $startDate->format('Y-m-d');
    $startDate->add(new DateInterval('P1D'));
}

foreach ($uniqueDates as $date) {
    if (isset($uniqueBatchSlotTimesMorning[$date])) {
        ksort($uniqueBatchSlotTimesMorning[$date]);
    }
    if (isset($uniqueBatchSlotTimesAfternoon[$date])) {
        ksort($uniqueBatchSlotTimesAfternoon[$date]);
    }
}
@endphp




@if ($schedules->isEmpty())
    <p>No schedules found for the specified criteria.</p>
@else
    <table style="border: 1px solid black; border-collapse: collapse; text-align: center;">
        <thead>
            <tr>
                <th colspan="{{ count($uniqueDates) * 6 + 1 }}" style="font-weight: bold; text-align:center;">
                    {{ $location }} - IIT NURTURE / ENTHUSE / LEADER -
                </th>
            </tr>
            <!-- Morning Table Header -->
            <tr>
                <th style="font-weight:bold; font-size:11pt; background-color:#F79646; color:black; border: 1px solid black; 
                    text-align:center; width: 28rem;">BATCH CODE</th>
                @foreach ($allDatesInRange as $date)
                    @php
                        $formattedDate = \Carbon\Carbon::parse($date)->format('D j M');
                        $uniqueSlotTimes = array_slice(array_keys($uniqueBatchSlotTimesMorning[$date] ?? []), 0, 3);
                        $uniqueSlotTimes = array_pad($uniqueSlotTimes, 3, null); // Ensure 3 slots by padding with null values if needed
                    @endphp
                    <th style="font-weight:bold;font-size:10.5pt; background-color:#F79646; text-align:center;
                        border: 1px solid black;" colspan="{{ count($uniqueSlotTimes) }}">{{ $formattedDate }}</th>
                @endforeach
            </tr>
            <tr>
                <th style="font-weight:bold; font-size:11pt; background-color:#F79646; color:black; border: 1px solid black; 
                    text-align: center;">TIMING</th>
                @foreach ($allDatesInRange as $date)
                    @php
                        $uniqueSlotTimes = array_slice(array_keys($uniqueBatchSlotTimesMorning[$date] ?? []), 0, 3);
                        $uniqueSlotTimes = array_pad($uniqueSlotTimes, 3, null); // Ensure 3 slots by padding with null values if needed
                    @endphp
                    @foreach ($uniqueSlotTimes as $slotTime)
                        @php
                            // Assuming $slotTime is in HH:mm AM/PM-HH:mm AM/PM format
                            if ($slotTime) {
                                list($startTime, $endTime) = explode('-', str_replace([' AM', ' PM'], ['', ''], $slotTime));

                                // Adjusting the display for the specific time range
                                if ($startTime === '11:45' && $endTime === '01:25') {
                                    $formattedTime = '11:00 - 1:25 PM';
                                } else {
                                    $formattedTime = \Carbon\Carbon::parse($startTime)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($endTime)->format('g:i A');
                                }

                                // Adjusting the format for times in the morning
                                if (\Carbon\Carbon::parse($startTime)->format('A') == 'AM' && $formattedTime !== '11:00 - 1:25 PM') {
                                    $formattedTime = \Carbon\Carbon::parse($startTime)->format('g:i') . ' - ' . \Carbon\Carbon::parse($endTime)->format('g:i A');
                                }
                            } else {
                                $formattedTime = ''; // Set an empty string for null values
                            }
                        @endphp
                        <th style="font-size:10pt; font-weight:bold; background-color:#F79646; color:black; 
                                border: 1px solid black; text-align: center;">{{ $formattedTime }}</th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($uniqueBatchFacultiesMorning as $batchCode => $batchDateTimes)
                <tr>
                    <td style="font-weight:bold; font-size:10pt; text-align:center; border: 1px solid black; background-color:#C4BD97; 
                            color:black; padding: 5px; justify-content: center;">
                        {{ $batchCode }}
                    </td>
                    @foreach ($allDatesInRange as $date)
                        @php
                            $uniqueSlotTimes = array_slice(array_keys($uniqueBatchSlotTimesMorning[$date] ?? []), 0, 3);
                        @endphp
                        @for ($i = 0; $i < 3; $i++)
                            @php
                                $time = $uniqueSlotTimes[$i] ?? null;
                                $schedulesForCell = $batchDateTimes[$date][$time] ?? [];
                                $subjectCode = !empty($schedulesForCell[0]['combined_code']) ? substr($schedulesForCell[0]['combined_code'], 0, 1) : null;
                                $color = $subjectColors[$subjectCode] ?? '#FFFFFF';
                            @endphp
                            <td style="font-weight:bold; font-size:8pt; height:20rem; width:15rem; text-align:center; 
                                    border: 1px solid black; background-color: {{ $color }}; padding: 5px;
                                    vertical-align: middle;">
                                @if (count($schedulesForCell) > 0)
                                    @foreach ($schedulesForCell as $schedule)
                                        <div>{{ $schedule['combined_code'] }}</div>
                                    @endforeach
                                @else
                                    @if (in_array($time, $uniqueSlotTimes) && empty($schedulesForCell))
                                        No Class
                                    @elseif (!in_array($time, $uniqueSlotTimes))
                                        Study Leave
                                    @endif
                                @endif
                            </td>
                        @endfor
                    @endforeach
                </tr>
            @endforeach
        </tbody>
<!-- Afternoon Table Header -->
<tr>
    <th style="font-weight:bold; font-size:11pt; background-color:#F79646; color:black; border: 1px solid black; 
        text-align: center;">TIMING</th>
    @foreach ($allDatesInRange as $date)
        @php
            $uniqueSlotTimes = array_keys($uniqueBatchSlotTimesAfternoon[$date] ?? []);
            $remainingSlots = 3 - count($uniqueSlotTimes);
        @endphp
        @for ($i = 0; $i < $remainingSlots; $i++)
            <th style="font-size:10pt; font-weight:bold; background-color:#F79646; color:black; 
                border: 1px solid black; text-align: center;"></th>
        @endfor
        @for ($i = 0; $i < count($uniqueSlotTimes); $i++)
            @php
                $slotTime = $uniqueSlotTimes[$i];
                $formattedTime = $slotTime ? str_replace([' AM', ' PM'], '', $slotTime) . ' PM' : '';
            @endphp
            <th style="font-size:10pt; font-weight:bold; background-color:#F79646; color:black; 
                border: 1px solid black; text-align: center;">{{ $formattedTime }}</th>
        @endfor
    @endforeach
</tr>

<!-- Afternoon Table Body -->
@foreach ($uniqueBatchFacultiesAfternoon as $batchCode => $batchDateTimes)
    <tr>
        <td style="font-weight:bold;font-size:8pt;height:20rem;text-align:center;background-color:#C4BD97; color:black;
                border: 1px solid black;vertical-align: middle;padding:5px;">
            {{ $batchCode }}
        </td>
        @foreach ($allDatesInRange as $date)
            @php
                $uniqueSlotTimes = array_keys($uniqueBatchSlotTimesAfternoon[$date] ?? []);
                $remainingSlots = 3 - count($uniqueSlotTimes);
                $hasSchedules = false;
            @endphp
            @for ($i = 0; $i < $remainingSlots; $i++)
                <td style="font-weight:bold; font-size:8pt; height:20rem; width:15rem; text-align:center; 
                    border: 1px solid black; background-color: #FFFFFF; padding: 5px;
                    vertical-align: middle;">
                    <!-- Show empty columns for remaining slots -->
                </td>
            @endfor
            @foreach ($uniqueSlotTimes as $slotTime)
                @php
                    $schedulesForCell = $batchDateTimes[$date][$slotTime] ?? [];
                    $subjectCode = !empty($schedulesForCell[0]['combined_code']) ? substr($schedulesForCell[0]['combined_code'], 0, 1) : null;
                    $color = $subjectColors[$subjectCode] ?? '#FFFFFF';
                @endphp
                <td style="font-weight:bold; font-size:8pt; height:20rem; width:15rem; text-align:center; 
                        border: 1px solid black; background-color: {{ $color }}; padding: 5px;
                        vertical-align: middle;">
                    @if (count($schedulesForCell) > 0)
                        @foreach ($schedulesForCell as $schedule)
                            <div>{{ $schedule['combined_code'] }}</div>
                        @endforeach
                    @else
                        @if (in_array($slotTime, $uniqueSlotTimes) && empty($schedulesForCell))
                            No Class
                        @elseif (!in_array($slotTime, $uniqueSlotTimes))
                            Study Leave
                        @endif
                    @endif
                </td>
            @endforeach
        @endforeach
    </tr>
@endforeach
    </table>
@endif
