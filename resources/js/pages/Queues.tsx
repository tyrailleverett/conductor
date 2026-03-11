import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorWorker } from '@/types';

interface WorkersResponse {
    data: ConductorWorker[];
    sync_driver?: boolean;
}

export default function Queues() {
    const [workers, setWorkers] = useState<ConductorWorker[]>([]);
    const [syncDriver, setSyncDriver] = useState(false);
    const [loading, setLoading] = useState(true);

    async function fetchWorkers() {
        try {
            const res = await apiGet<WorkersResponse>('/workers');
            setWorkers(res.data);
            setSyncDriver(res.sync_driver ?? false);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        fetchWorkers();
        const interval = setInterval(fetchWorkers, 15000);
        return () => { clearInterval(interval); };
    }, []);

    return (
        <div className="space-y-4">
            <div>
                <h1 className="text-lg font-semibold">Queues</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Worker health and activity</p>
            </div>

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
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Worker</TableHead>
                            <TableHead>Queue</TableHead>
                            <TableHead>Hostname</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Current Job</TableHead>
                            <TableHead>Last Heartbeat</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {workers.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center text-[var(--muted-foreground)]">No workers connected.</TableCell>
                            </TableRow>
                        )}
                        {workers.map((worker) => (
                            <TableRow key={worker.id}>
                                <TableCell className="font-mono text-xs">{worker.worker_name}</TableCell>
                                <TableCell>{worker.queue}</TableCell>
                                <TableCell className="font-mono text-xs text-[var(--muted-foreground)]">{worker.hostname}</TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-1.5">
                                        <span className={`h-2 w-2 rounded-full ${worker.status === 'idle' ? 'bg-green-400' : worker.status === 'busy' ? 'bg-blue-400' : 'bg-red-400'}`} />
                                        <Badge variant="status" status={worker.status}>{worker.status}</Badge>
                                    </div>
                                </TableCell>
                                <TableCell className="font-mono text-xs text-[var(--muted-foreground)]">
                                    {worker.current_job_uuid ?? '—'}
                                </TableCell>
                                <TableCell className="text-xs text-[var(--muted-foreground)]">
                                    {formatRelativeTime(worker.last_heartbeat_at)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </div>
    );
}
