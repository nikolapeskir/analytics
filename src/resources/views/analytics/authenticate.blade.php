@if(!$connected)
    <a href="{{ route('ga.connect') }}">Connect</a>
@else
    Connected. <a href="{{ route('ga.index') }}">Choose View</a>
    <br />
    <a href="{{ route('ga.disconnect') }}">Disconnect</a>
@endif