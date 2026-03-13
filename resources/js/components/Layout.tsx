import React from 'react';
import { Outlet, NavLink } from 'react-router';
import { cn } from '@/lib/utils';

const navItems = [
    { to: '/', label: 'Overview', icon: '▦', end: true },
    { to: '/jobs', label: 'Jobs', icon: '⚡', end: false },
    { to: '/workflows', label: 'Workflows', icon: '⇄', end: false },
    { to: '/events', label: 'Events', icon: '◎', end: false },
    { to: '/webhooks', label: 'Webhooks', icon: '↗', end: false },
    { to: '/schedules', label: 'Schedules', icon: '◷', end: false },
    { to: '/metrics', label: 'Metrics', icon: '▲', end: false },
    { to: '/queues', label: 'Queues', icon: '☰', end: false },
];

export default function Layout() {
    return (
        <div className="flex h-screen bg-[var(--background)] text-[var(--foreground)]">
            <aside className="flex w-44 shrink-0 flex-col border-r border-[var(--border)] bg-[var(--background)]">
                <div className="flex h-14 items-center border-b border-[var(--border)] px-4">
                    <span className="font-mono text-sm font-bold text-[var(--primary)]">◆</span>
                    <span className="ml-2 text-sm font-semibold tracking-tight">Conductor</span>
                </div>
                <nav className="flex flex-1 flex-col gap-0.5 px-2 py-3">
                    {navItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.end}
                            className={({ isActive }) =>
                                cn(
                                    'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors',
                                    isActive
                                        ? 'border-l-2 border-[var(--primary)] bg-[var(--primary)]/10 pl-[10px] text-[var(--primary)]'
                                        : 'text-[var(--muted-foreground)] hover:bg-[var(--muted)] hover:text-[var(--foreground)]',
                                )
                            }
                        >
                            <span className="text-xs">{item.icon}</span>
                            {item.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>
            <div className="flex flex-1 flex-col overflow-hidden">
                <header className="flex h-14 items-center border-b border-[var(--border)] px-6">
                    <span className="text-sm font-semibold tracking-tight">Conductor</span>
                </header>
                <main className="flex-1 overflow-auto p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
