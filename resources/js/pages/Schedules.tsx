import React, { useEffect, useState } from 'react';
import { apiGet, apiPost } from '@/lib/api';
import { Toggle } from '@/components/ui/toggle';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorSchedule } from '@/types';

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : 'bg-zinc-500 text-zinc-400';
    return (
        <span className="flex items-center gap-1.5 text-sm">
            <span className={`inline-block h-1.5 w-1.5 rounded-full ${color.split(' ')[0]}`} />
            <span className={`capitalize ${color.split(' ')[1]}`}>{status}</span>
        </span>
    );
}

function formatDateTime(iso: string | null): string {
    if (!iso) { return '—'; }
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}

export default function Schedules() {
    const [schedules, setSchedules] = useState<ConductorSchedule[]>([]);
    const [loading, setLoading] = useState(true);
    const [toggling, setToggling] = useState<Set<number>>(new Set());

    useEffect(() => {
        apiGet<{ data: ConductorSchedule[] }>('/schedules')
            .then((res) => { setSchedules(res.data); })
            .finally(() => { setLoading(false); });
    }, []);

    async function handleToggle(schedule: ConductorSchedule) {
        setToggling((prev) => new Set([...prev, schedule.id]));
        try {
            const res = await apiPost<{ data: ConductorSchedule }>(`/schedules/${schedule.id}/toggle`);
            setSchedules((prev) => prev.map((s) => (s.id === schedule.id ? res.data : s)));
        } finally {
            setToggling((prev) => {
                const next = new Set(prev);
                next.delete(schedule.id);
                return next;
            });
        }
    }

    return (
        <div className="space-y-5">
            <div>
                <h1 className="text-xl font-semibold text-[var(--foreground)]">Schedules</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Manage registered scheduled functions</p>
            </div>

            {loading ? (
                <div className="space-y-2">
                    {[...Array(3)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--border)]">
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Name</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Cron</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Active</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Last Run</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Next Run</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Last Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {schedules.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No schedules registered.</td>
                                </tr>
                            )}
                            {schedules.map((schedule) => (
                                <tr key={schedule.id} className="border-b border-[var(--border)] last:border-0">
                                    <td className="px-4 py-3">
                                        <div className="font-mono text-sm text-[var(--foreground)]">{schedule.display_name}</div>
                                        <div className="mt-0.5 font-mono text-xs text-[var(--muted-foreground)]">{schedule.function_class}</div>
                                    </td>
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--foreground)]">{schedule.cron_expression}</td>
                                    <td className="px-4 py-3">
                                        <Toggle
                                            checked={schedule.is_active}
                                            onCheckedChange={() => { handleToggle(schedule); }}
                                            disabled={toggling.has(schedule.id)}
                                        />
                                    </td>
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--muted-foreground)]">
                                        {formatDateTime(schedule.last_run_at)}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--muted-foreground)]">
                                        {formatDateTime(schedule.next_run_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {schedule.last_run_status ? (
                                            <StatusDot status={schedule.last_run_status} />
                                        ) : (
                                            <span className="text-[var(--muted-foreground)]">—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
