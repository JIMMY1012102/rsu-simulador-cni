@php
    $assets = [
        'home' => 'House', 'user' => 'UserRound', 'cap' => 'GraduationCap',
        'book' => 'BookOpen', 'help' => 'CircleHelp', 'chat' => 'MessagesSquare',
        'logout' => 'LogOut', 'help-top' => 'CircleHelp1', 'lock' => 'LockKeyhole',
        'cap-button' => 'GraduationCapBtn', 'check-stat' => 'CheckStat',
        'cap-stat' => 'GraduationCapStat', 'user-stat' => 'UserStat',
        'simulator-stat' => 'RocketStat', 'user-personal' => 'UserPersonal',
        'edit' => 'Pencil', 'check' => 'Check', 'book-resource' => 'BookOpen1',
        'help-resource' => 'CircleHelp2', 'chat-resource' => 'MessagesSquare1',
        'arrow' => 'ArrowUpRight',
    ];
@endphp
<img class="alumno-svg" src="{{ asset('images/alumno/figma/'.$assets[$name].'.svg') }}" alt="" aria-hidden="true">
