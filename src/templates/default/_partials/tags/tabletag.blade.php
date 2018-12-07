<table class="table table-hover">
    @foreach($table as $items)
        <tr>
        @foreach($items as $item)
            <td>{{ $item }}</td>
        @endforeach
        </tr>
    @endforeach
</table>