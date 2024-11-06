<aside class="main-sidebar sidebar-dark-primary bg-navy elevation-4">
    <a href=" {{ route('gate.dashboard') }} " class="brand-link bg-navy text-center">
        <img src="{{ asset('img/icarry-logo-white.png') }}" alt="Logo"
            class="brand-image img-circle elevation-3">
        <span class="brand-text font-weight-light text-yellow float-left">中繼管理系統</span>
    </a>
    <div class="sidebar">
        <nav id="sidebar" class="mt-2 nav-compact">

            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ url('dashboard') }}" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p class="text-sm">首頁</p>
                    </a>
                </li>
                @foreach($mainmenus as $mainmenu)
                @if(in_array($mainmenu->code,explode(',',Auth::user()->power ?? '' )))
                @if($mainmenu->type == 3)
                @if($mainmenu->url_type == 1)
                <li class="nav-item">
                    <a href="{{ url($mainmenu->url) }}" class="nav-link" {{ $mainmenu->open_window ? 'target="_blank"' : '' }}>
                        {!! $mainmenu->fa5icon !!}
                        <p class="text-sm">
                            {{ $mainmenu->name }}
                        </p>
                    </a>
                </li>
                @elseif($mainmenu->url_type == 2)
                <li class="nav-item">
                    <a href="{{ $mainmenu->url }}" class="nav-link" {{ $mainmenu->open_window ? 'target="_blank"' : '' }}>
                        {!! $mainmenu->fa5icon !!}
                        <p class="text-sm">
                            {{ $mainmenu->name }}
                        </p>
                    </a>
                </li>
                @else
                <li class="nav-item has-treeview">
                    <a href="{{ $mainmenu->url ? $mainmenu->url : 'javascript:' }}" class="nav-link" {{ $mainmenu->open_window ? 'target="_blank"' : '' }}>
                        {!! $mainmenu->fa5icon !!}
                        <p class="text-sm">
                            {{ $mainmenu->name }}
                            <i class="right fas fa-angle-left"></i>
                            @if($mainmenu->code == 'M29S0')
                                @if($sellAbnormalCount > 0)
                                <span class="right badge badge-danger">{{ $sellAbnormalCount }}</span>
                                @endif
                                @if($stockinAbnormalCount > 0)
                                <span class="right badge badge-warning">{{ $stockinAbnormalCount }}</span>
                                @endif
                                @if($vendorSellImport > 0)
                                <span class="right badge badge-danger">{{ $vendorSellImport }}</span>
                                @endif
                                @if($warehouseSellImport > 0)
                                <span class="right badge badge-warning">{{ $warehouseSellImport }}</span>
                                @endif
                            @endif
                            @if($mainmenu->code == 'M27S0')
                                @if($orderImportAbnormalCount > 0)
                                <span class="right badge badge-warning">{{ $orderImportAbnormalCount }}</span>
                                @endif
                                @if($orderCancelCount > 0)
                                <span class="right badge badge-danger">{{ $orderCancelCount }}</span>
                                @endif
                                @if($chinaOrderCount > 0)
                                <span class="right badge badge-danger">{{ $chinaOrderCount }}</span>
                                @endif
                            @endif
                            @if($mainmenu->code == 'M33S0')
                                @if($sellReturnItemCount > 0)
                                <span class="right badge badge-warning">{{ $sellReturnItemCount }}</span>
                                @endif
                                @if($requisitionAbnormalCount > 0)
                                <span class="right badge badge-danger">{{ $requisitionAbnormalCount }}</span>
                                @endif
                            @endif
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                    @foreach($mainmenu->submenu as $submenu)
                    @if(in_array($submenu->code,explode(',',Auth::user()->power ?? '' )))
                    @if($submenu->code == 'M1S2' && in_array(Auth::user()->id,[1,2,40]))
                    <li class="nav-item">
                        <a href="{{ $submenu->url ? url($submenu->url) : 'javascript:' }}" class="nav-link" {{ $submenu->open_window ? 'target="_blank"' : '' }}>
                            {!! $submenu->fa5icon !!}
                            <p class="text-sm">{{ $submenu->name }}</p>
                        </a>
                    </li>
                    @elseif($submenu->code != 'M1S2')
                    <li class="nav-item">
                        <a href="{{ $submenu->url ? url($submenu->url) : 'javascript:' }}" class="nav-link" {{ $submenu->open_window ? 'target="_blank"' : '' }}>
                            {!! $submenu->fa5icon !!}
                            <p class="text-sm">{{ $submenu->name }}</p>
                            @if($submenu->code == 'M29S2')
                                @if($sellAbnormalCount > 0)
                                <span class="badge badge-danger">{{ $sellAbnormalCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M29S3')
                                @if($stockinAbnormalCount > 0)
                                <span class="badge badge-warning">{{ $stockinAbnormalCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M29S4')
                                @if($vendorSellImport > 0)
                                <span class="badge badge-danger">{{ $vendorSellImport }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M29S5')
                                @if($warehouseSellImport > 0)
                                <span class="badge badge-warning">{{ $warehouseSellImport }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M27S2')
                                @if($orderImportAbnormalCount > 0)
                                <span class="badge badge-warning">{{ $orderImportAbnormalCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M27S3')
                                @if($orderCancelCount > 0)
                                <span class="badge badge-danger">{{ $orderCancelCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M27S5')
                                @if($chinaOrderCount > 0)
                                <span class="badge badge-danger">{{ $chinaOrderCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M33S2')
                                @if($sellReturnItemCount > 0)
                                <span class="right badge badge-warning">{{ $sellReturnItemCount }}</span>
                                @endif
                            @endif
                            @if($submenu->code == 'M33S3')
                                @if($requisitionAbnormalCount > 0)
                                <span class="right badge badge-danger">{{ $requisitionAbnormalCount }}</span>
                                @endif
                            @endif
                        </a>
                    </li>
                    @endif
                    @endif
                    @endforeach
                    </ul>
                </li>
                @endif
                @endif
                @endif
                @endforeach
                {{-- 登出 --}}
                <li class="nav-item">
                    <a href="{{ route('gate.logout') }}" class="nav-link">
                        <i class="nav-icon fas fa-door-open text-danger"></i>
                        <p class="text-sm">登出 (Logout)</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>


<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
