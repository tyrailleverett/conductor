import React from 'react';
import { Outlet, NavLink } from 'react-router';
import { cn } from '@/lib/utils';
import {
    LayoutDashboard,
    Briefcase,
    GitFork,
    Zap,
    Calendar,
    BarChart2,
    Database,
    Activity,
} from 'lucide-react';

const navItems = [
    { to: '/', label: 'Overview', icon: LayoutDashboard, end: true },
    { to: '/jobs', label: 'Jobs', icon: Briefcase, end: false },
    { to: '/workflows', label: 'Workflows', icon: GitFork, end: false },
    { to: '/events', label: 'Events', icon: Zap, end: false },
    { to: '/schedules', label: 'Schedules', icon: Calendar, end: false },
    { to: '/metrics', label: 'Metrics', icon: BarChart2, end: false },
    { to: '/queues', label: 'Queues', icon: Database, end: false },
];

export default function Layout() {
    return (
        <div className="flex h-screen bg-[var(--background)] text-[var(--foreground)]">
            <aside className="flex w-56 shrink-0 flex-col border-r border-[var(--border)] bg-[var(--background)]">
                <div className="flex h-14 items-center border-b border-[var(--border)] px-4 gap-2.5">
                    <Activity className="h-5 w-5 text-[var(--primary)] shrink-0" strokeWidth={2.5} />
                    <span className="text-sm font-semibold tracking-tight text-[var(--foreground)]">Fluxrun</span>
                </div>
                <nav className="flex flex-1 flex-col gap-0.5 px-2 py-3">
                    {navItems.map((item) => {
                        const Icon = item.icon;
                        return (
                            <NavLink
                                key={item.to}
                                to={item.to}
                                end={item.end}
                                className={({ isActive }) =>
                                    cn(
                                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                                        isActive
                                            ? 'bg-[var(--primary)]/10 text-[var(--primary)] font-medium'
                                            : 'text-[var(--muted-foreground)] hover:bg-[var(--muted)] hover:text-[var(--foreground)]',
                                    )
                                }
                            >
                                <Icon className="h-4 w-4 shrink-0" />
                                {item.label}
                            </NavLink>
                        );
                    })}
                </nav>
                <div className="px-4 py-3 border-t border-[var(--border)]">
                    <span className="text-xs text-[var(--muted-foreground)]">v1.0.0-alpha</span>
                </div>
            </aside>
            <main className="flex-1 overflow-auto p-8">
                <Outlet />
            </main>
        </div>
    );
}
