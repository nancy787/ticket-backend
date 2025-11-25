<nav class="mt-2">
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <li class="nav-item menu-open">
 
      <a href="{{ route('users') }}" class="nav-link {{ Request::is('users*') ? 'active' : '' }}">
          <i class="nav-icon far fa-fw fa-user"></i>
          <p>Users</p>
        </a>

        <a href="{{ route('event.index') }}" class="nav-link {{ Request::is('event*') ? 'active' : '' }}">
          <i class="nav-icon far fa-calendar-alt"></i>
          <p>
            Events
          </p>
        </a>
        <a href="{{ route('ticket.index') }}"  class="nav-link {{ Request::routeIs('ticket.index') ? 'active' : '' }}">
          <i class="nav-icon far fas fa-ticket-alt"></i>
          <p>
            Tickets
          </p>
        </a>
        <a href="{{ route('ticket.ticket_categories') }}" class="nav-link {{ Request::routeIs('ticket.ticket_categories') ? 'active' : '' }}">
          <i class="nav-icon far fas fa-ticket-alt"></i>
          <p>
            Ticket Categories
          </p>
        </a>
        
        <a href="{{ route('ticket.addons') }}" class="nav-link {{ Request::routeIs('ticket.addons') ? 'active' : '' }}">
          <i class="nav-icon far fas fa-plus"></i>
          <p>
            Addons
          </p>
        </a>

        <a href="{{ route('report.index') }}" class="nav-link {{ Request::routeIs('report.index') ? 'active' : '' }}">
          <i class="nav-icon far fas fa-file"></i>
          <p>
            Reports
          </p>
        </a>
        <a href="{{ route('notification.index') }}" class="nav-link {{ Request::routeIs('notification.index') ? 'active' : '' }}">
          <i class="nav-icon far fas fa-bell"></i>
          <p>
            Notification
          </p>
        </a>
          <li class="nav-item has-treeview {{ Request::is('profile*') || Request::is('banner*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ Request::is('profile*') || Request::is('banner*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-cog"></i> 
              <p>
                Settings
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('profile.edit')}}" class="nav-link {{ Request::is('profile*') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-user-cog"></i>
                  <p>Profile setting</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('banner.index')}}" class="nav-link {{ Request::is('banner*') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-image"></i>
                  <p>Banner setting</p>
                </a>
              </li>
            </ul>
          </li>
          <a href="{{ route('help-and-suppport') }}" target="_blank" class="nav-link {{ Request::routeIs('help-and-suppport') ? 'active' : '' }}" hidden>
            <i class="fas fa-question-circle nav-icon"></i>
            <p>
                Help & Support
            </p>
          </a>

          <a href="{{ route('terms-and-condition') }}" target="_blank" class="nav-link {{ Request::routeIs('terms-and-condition') ? 'active' : '' }}">
          <i class="fas fa-file-alt nav-icon"></i>
          <p>
            Terms & Conditions
          </p>
        </a>
        <a href="{{ route('change-terms-and-condition', 1) }}" class="nav-link {{ Request::routeIs('change-terms-and-condition') ? 'active' : '' }}">
          <i class="fas fa-edit nav-icon"></i>
          <p>
            Change Terms
          </p>
        </a>
        <a href="{{ route('f_a_ques') }}" class="nav-link {{ Request::routeIs('f_a_ques') ? 'active' : '' }}">
          <i class="fas fa-question-circle nav-icon"></i>
          <p>
            FAQs
          </p>
        </a>
      </li>
  </ul>
</nav>
