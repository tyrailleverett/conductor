import React, { useEffect, useState } from 'react';
import { apiGet, apiPost } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Toggle } from '@/components/ui/toggle';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorSchedule } from '@/types';

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
        <div className="space-y-4">
            <div>
                <h1 className="text-lg font-semibold">Schedules</h1>
                <p className="text-sm text-[var(--muted-foreground)]">{schedules.length} registered</p>
            </div>

            {loading ? (
                <div className="space-y-2">
                    {[...Array(3)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Function Class</TableHead>
                            <TableHead>Cron</TableHead>
                            <TableHead>Active</TableHead>
                            <TableHead>Last Run</TableHead>
                            <TableHead>Last Status</TableHead>
                            <TableHead>Next Run</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {schedules.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center text-[var(--muted-foreground)]">No schedules registered.</TableCell>
                            </TableRow>
                        )}
                        {schedules.map((schedule) => (
                            <TableRow key={schedule.id}>
                                <TableCell className="text-sm">{schedule.display_name}</TableCell>
                                <TableCell className="font-mono text-xs">{schedule.function_class}</TableCell>
                                <TableCell className="font-mono text-xs">{schedule.cron_expression}</TableCell>
                                <TableCell>
                                    <Toggle
                                        checked={schedule.is_active}
                                        onCheckedChange={() => { handleToggle(schedule); }}
                                        disabled={toggling.has(schedule.id)}
                                    />
                                </TableCell>
                                <TableCell className="text-[var(--muted-foreground)] text-xs">
                                    {schedule.last_run_at ? formatRelativeTime(schedule.last_run_at) : '—'}
                                </TableCell>
                                <TableCell>
                                    {schedule.last_run_status ? (
                                        <Badge variant="status" status={schedule.last_run_status}>{schedule.last_run_status}</Badge>
                                    ) : '—'}
                                </TableCell>
                                <TableCell className="text-[var(--muted-foreground)] text-xs">
                                    {schedule.next_run_at ? formatRelativeTime(schedule.next_run_at) : '—'}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </div>
    );
}
