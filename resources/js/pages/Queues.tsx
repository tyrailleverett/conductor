import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { RefreshCw } from 'lucide-react';
import type { ConductorWorker } from '@/types';

interface WorkersResponse {
    data: ConductorWorker[];
    sync_driver?: boolean;
}

function WorkerStatus({ status }: { status: string }) {
    const dotColor =
        status === 'busy' ? 'bg-green-400'
        : status === 'idle' ? 'bg-zinc-500'
        : 'bg-red-400';
    const textColor =
        status === 'busy' ? 'text-green-400'
        : status === 'idle' ? 'text-[var(--muted-foreground)]'
        : 'text-red-400';
    const label = status === 'busy' ? 'Active' : status === 'idle' ? 'Idle' : 'Offline';
    return (
        <span className="flex items-center gap-1.5 text-sm">
            <span className={`inline-block h-1.5 w-1.5 rounded-full ${dotColor}`} />
            <span className={`capitalize ${textColor}`}>{label}</span>
        </span>
    );
}

export default function Queues() {
    const [workers, setWorkers] = useState<ConductorWorker[]>([]);
    const [syncDriver, setSyncDriver] = useState(false);
    const [loading, setLoading] = useState(true);
    const [lastRefreshed, setLastRefreshed] = useState<Date>(new Date());
    const [secondsAgo, setSecondsAgo] = useState(0);

    async function fetchWorkers() {
        try {
            const res = await apiGet<WorkersResponse>('/workers');
            setWorkers(res.data);
            setSyncDriver(res.sync_driver ?? false);
            setLastRefreshed(new Date());
            setSecondsAgo(0);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        fetchWorkers();
        const interval = setInterval(fetchWorkers, 15000);
        return () => { clearInterval(interval); };
    }, []);

    // Tick seconds counter
    useEffect(() => {
        const tick = setInterval(() => {
            setSecondsAgo(Math.floor((Date.now() - lastRefreshed.getTime()) / 1000));
        }, 1000);
        return () => { clearInterval(tick); };
    }, [lastRefreshed]);

    return (
        <div className="space-y-5">
            <div className="flex items-start justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-[var(--foreground)]">Queues</h1>
                    <p className="text-sm text-[var(--muted-foreground)]">Monitor queue worker health</p>
                </div>
                <button
                    className="flex items-center gap-1.5 rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] hover:bg-[var(--muted)]/40 transition-colors"
                    onClick={fetchWorkers}
                >
                    <RefreshCw className="h-3.5 w-3.5" />
                    Refresh
                </button>
            </div>

            <p className="font-mono text-xs text-[var(--muted-foreground)]">
                Auto-refreshing every 15s — last refreshed {secondsAgo}s ago
            </p>

            {syncDriver && (
                <Alert variant="warning">
                    <AlertTitle>Sync driver detected</AlertTitle>
                    <AlertDescription>
                        Worker health monitoring is unavailable when using the sync queue driver. Use a real queue driver (database, redis, etc.) to see worker status.
                    </AlertDescription>
                </Alert>
            )}

            {loading ? (
                <div className="space-y-2">
                    {[...Array(3)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--border)]">
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Worker</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Queue</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Hostname</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Status</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Current Job</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Last Heartbeat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {workers.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No workers connected.</td>
                                </tr>
                            )}
                            {workers.map((worker) => (
                                <tr key={worker.id} className="border-b border-[var(--border)] last:border-0">
                                    <td className="px-4 py-3 font-mono text-sm font-semibold text-[var(--foreground)]">{worker.worker_name}</td>
                                    <td className="px-4 py-3 text-sm text-[var(--foreground)]">{worker.queue}</td>
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--muted-foreground)]">{worker.hostname}</td>
                                    <td className="px-4 py-3">
                                        <WorkerStatus status={worker.status} />
                                    </td>
                                    <td className="px-4 py-3 font-mono text-sm">
                                        {worker.current_job_uuid ? (
                                            <span className="text-green-400">{worker.current_job_uuid}</span>
                                        ) : (
                                            <span className="text-[var(--muted-foreground)]">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-[var(--muted-foreground)]">
                                        {formatRelativeTime(worker.last_heartbeat_at)}
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
