import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorEvent } from '@/types';

export default function EventDetail() {
    const { id } = useParams<{ id: string }>();
    const [event, setEvent] = useState<ConductorEvent | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!id) { return; }
        setLoading(true);
        apiGet<{ data: ConductorEvent }>(`/events/${id}`)
            .then((res) => { setEvent(res.data); })
            .finally(() => { setLoading(false); });
    }, [id]);

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-64" />
                <Skeleton className="h-32 rounded-lg" />
            </div>
        );
    }

    if (!event) {
        return <p className="text-[var(--muted-foreground)]">Event not found.</p>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="font-mono text-sm font-semibold">{event.name}</h1>
                <p className="text-xs text-[var(--muted-foreground)] mt-1">{formatRelativeTime(event.dispatched_at)}</p>
            </div>

            <Card>
                <CardHeader><CardTitle>Payload</CardTitle></CardHeader>
                <CardContent>
                    <pre className="rounded bg-[var(--muted)] p-3 font-mono text-xs text-[var(--foreground)] overflow-auto max-h-64">
                        {JSON.stringify(event.payload, null, 2)}
                    </pre>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Triggered Runs</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Function Class</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Attempts</TableHead>
                                <TableHead>Duration</TableHead>
                                <TableHead>Error</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(event.runs ?? []).length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-[var(--muted-foreground)]">No runs.</TableCell>
                                </TableRow>
                            )}
                            {(event.runs ?? []).map((run) => (
                                <TableRow key={run.id}>
                                    <TableCell className="font-mono text-xs">{run.function_class}</TableCell>
                                    <TableCell><Badge variant="status" status={run.status}>{run.status}</Badge></TableCell>
                                    <TableCell>{run.attempts}</TableCell>
                                    <TableCell>{formatDuration(run.duration_ms)}</TableCell>
                                    <TableCell className="text-red-400 text-xs">{run.error_message ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
