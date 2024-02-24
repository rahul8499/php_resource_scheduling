<table>
    <thead>
        <tr>
          <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:28rem;">BATCH CODE</th> 
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:15rem;">DURATION</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:15rem;">DURATION TYPE</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:15rem;">STARTING DATE</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:40rem;">SELECTED DAYS</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;width:15rem;">LOCATION</th>

            
        </tr>
    </thead>
    <tbody>
   @foreach ($batches as $batch)
    <tr>
        <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">{{ $batch->batch_code }}</td> 
        <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">{{ $batch->duration }}</td>
        <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">{{ $batch->duration_type }}</td>
        <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">{{ $batch->starting_date }}</td>
        <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">
            @if (is_array($batch->selected_days))
                {{ implode(', ', $batch->selected_days) }}
            @else
                {{ $batch->selected_days }}
            @endif
        </td>
<td style="background-color:#C4BD97;text-align: center;border: 1px solid black;">
    @foreach ($batch->locations as $location)
        {{ $location->name }}
        @if (!$loop->last) , @endif
    @endforeach
</td>
    </tr>
@endforeach



    </tbody>
</table>
