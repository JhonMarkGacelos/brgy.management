{{-- Shared notification bell — used in admin, staff, and resident layouts --}}
<div x-data="notificationBell()" x-init="init()" class="relative">
    <button @click="toggle()"
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
        <i class="fa-solid fa-bell text-[15px]"></i>
        <span x-show="unreadCount > 0" x-cloak
              class="absolute -top-0.5 -right-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white"
              x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
    </button>

    <div x-show="open" x-cloak
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] rounded-2xl bg-white border border-gray-100 shadow-xl overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Notifications</p>
            <button x-show="unreadCount > 0" @click="markAllRead()"
                    class="text-[11px] font-semibold text-green-700 hover:text-green-800">
                Mark all as read
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
            <template x-if="notifications.length === 0">
                <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                    <i class="fa-regular fa-bell-slash text-2xl mb-2"></i>
                    <p class="text-xs text-gray-400">No notifications yet</p>
                </div>
            </template>

            <template x-for="n in notifications" :key="n.id">
                <a :href="n.url" @click="markRead(n)"
                   class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors"
                   :class="!n.read ? 'bg-green-50/40' : ''">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                         :style="'background-color:' + colorBg(n.color)">
                        <i class="fa-solid text-[12px]" :class="n.icon" :style="'color:' + colorFg(n.color)"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-gray-800 leading-tight" x-text="n.title"></p>
                        <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="n.body"></p>
                        <p class="text-[10px] text-gray-400 mt-1" x-text="n.time_ago"></p>
                    </div>
                    <span x-show="!n.read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-600"></span>
                </a>
            </template>
        </div>

        <a href="{{ route('notifications.index') }}"
           class="block text-center px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 border-t border-gray-100 transition-colors">
            View All Notifications
        </a>
    </div>
</div>

<script>
    function notificationBell() {
        return {
            open: false,
            notifications: [],
            unreadCount: 0,
            init() {
                this.fetchNotifications();
                setInterval(() => this.fetchNotifications(), 30000);
            },
            toggle() {
                this.open = !this.open;
                if (this.open) this.fetchNotifications();
            },
            fetchNotifications() {
                axios.get('{{ route('notifications.recent') }}')
                    .then(res => {
                        this.notifications = res.data.notifications;
                        this.unreadCount = res.data.unread_count;
                    })
                    .catch(() => {});
            },
            markRead(n) {
                if (n.read) return;
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                axios.post('{{ url('/notifications') }}/' + n.id + '/read').catch(() => {});
            },
            markAllRead() {
                this.notifications.forEach(n => n.read = true);
                this.unreadCount = 0;
                axios.post('{{ route('notifications.read-all') }}').catch(() => {});
            },
            colorBg(color) {
                const map = { green: '#dcfce7', red: '#fee2e2', blue: '#dbeafe', amber: '#fef3c7', purple: '#f3e8ff', gray: '#f3f4f6' };
                return map[color] || map.gray;
            },
            colorFg(color) {
                const map = { green: '#16a34a', red: '#dc2626', blue: '#2563eb', amber: '#d97706', purple: '#9333ea', gray: '#6b7280' };
                return map[color] || map.gray;
            },
        }
    }
</script>
