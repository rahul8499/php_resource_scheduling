<table>
    <thead>
        <tr>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Faculty Code</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">First Name</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Last Name</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Email</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Phone</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Gender</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Address</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Age</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Experience</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Image</th>
            <th style="font-weight:bold;background-color:#F79646;text-align: center;border: 1px solid black;">Location</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($faculties as $faculty)
            <tr>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->faculty_code }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->first_name }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->last_name }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:25rem;">{{ $faculty->mail }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->phone }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->gender }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->address }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:8rem;">{{ $faculty->age }}</td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:15rem;">{{ $faculty->experience }}</td>
                <td>
                    @if ($faculty->image_url)
                        <img src="{{ $faculty->image_url }}" alt="{{ $faculty->first_name }} Image" width="100">
                    @else
                        No Image
                    @endif
                </td>
                <td style="background-color:#C4BD97;text-align: center;border: 1px solid black;width:20rem;">
                    @foreach ($faculty->location as $location)
                        {{ $location->name }}
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
