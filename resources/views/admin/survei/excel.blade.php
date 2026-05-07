<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Orang Tua</th>
            <th>Waktu</th>
            @foreach($survei->pertanyaan as $p)
                <th>{{ $p->pertanyaan }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($respon as $i => $r)
            @php
                $j = is_array($r->jawaban) ? $r->jawaban : json_decode($r->jawaban, true);
                $j = $j ?? [];
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->ortu->name ?? '—' }}</td>
                <td>{{ date('d M Y H:i', strtotime($r->created_at)) }}</td>
                @foreach($survei->pertanyaan as $p)
                    @php
                        $val = $j[$p->id] ?? null;
                        if (is_array($val))
                            $val = implode(', ', $val);
                    @endphp
                    <td>{{ $val ?: '—' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>