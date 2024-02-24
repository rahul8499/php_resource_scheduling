@php
    $facultyColors = ['#A1D490', '#97C2FC', '#FFC09F', '#F2A1A8']; // You can add more colors here
    $colorIndex = 0;
@endphp

<table>
    <thead>
        <tr>
            <th style="font-weight:bold">FACULTY NAME</th>
            <th style="font-weight:bold">DATES</th>
            <th style="font-weight:bold">BATCH_SLOT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($leaves as $leave)
            @php
                $faculty = App\Models\Faculty::find($leave->faculty_id); // Replace with your actual faculty model
                $facultyColor = $facultyColors[$colorIndex % count($facultyColors)];
                $colorIndex++;
            @endphp
            <tr>
                <td>
                    <div style="background-color: {{ $facultyColor }}">
                        {{ optional($faculty)->first_name }}
                    </div>
                </td>
                <td>
                    @php
                        $dates = explode(',', $leave->dates);
                    @endphp
                    @foreach ($dates as $date)
                        <div>
                            <p>{{ \Carbon\Carbon::parse(trim($date))->format('Y-m-d') ?? 'Invalid Date' }}</p>
                        </div>
                    @endforeach
                </td>
                <td>
                    <div>
                        @if (!empty($leave->batch_slot_id))
                            <p>{{ optional(App\Models\BatchSlot::find($leave->batch_slot_id))->name ?? 'Invalid Batch Slot' }}</p>
                        @else
                            <p>Invalid Batch Slot</p>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
