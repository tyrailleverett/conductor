import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import { apiGet, apiPost, apiDelete } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { useLogStream } from '@/hooks/useLogStream';
import type { ConductorJob, ConductorJobLog } from '@/types';

export default function JobDetail() {
    const { id } = useParams<{ id: string }>();
    const [job, setJob] = useState<ConductorJob | null>(null);
    const [loading, setLoading] = useState(true);
    const [stackTraceOpen, setStackTraceOpen] = useState(false);
    const [actionLoading, setActionLoading] = useState(false);

    async function loadJob() {
        if (!id) { return; }
        setLoading(true);
        try {
            const res = await apiGet<{ data: ConductorJob }>(`/jobs/${id}`);
            setJob(res.data);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { loadJob(); }, [id]);

    const isRunning = job?.status === 'running';
    const streamedLogs = useLogStream(id ?? '', isRunning);

    const allLogs = React.useMemo<ConductorJobLog[]>(() => {
        const base = job?.logs ?? [];
        const streamed = streamedLogs.filter((sl) => !base.some((bl) => bl.id === sl.id));
        return [...base, ...streamed].sort((a, b) => a.id - b.id);
    }, [job?.logs, streamedLogs]);

    async function handleRetry() {
        if (!id) { return; }
        setActionLoading(true);
        try {
            await apiPost(`/jobs/${id}/retry`);
            await loadJob();
        } finally {
            setActionLoading(false);
        }
    }

    async function handleCancel() {
        if (!id) { return; }
        setActionLoading(true);
        try {
            await apiDelete(`/jobs/${id}`);
            await loadJob();
        } finally {
            setActionLoading(false);
        }
    }

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-64" />
                <Skeleton className="h-32 rounded-lg" />
                <Skeleton className="h-48 rounded-lg" />
            </div>
        );
    }

    if (!job) {
        return <p className="text-[var(--muted-foreground)]">Job not found.</p>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-start justify-between">
                <div>
                    <h1 className="font-mono text-sm font-semibold">{job.display_name}</h1>
                    <p className="text-xs text-[var(--muted-foreground)] mt-1">{job.id}</p>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="status" status={job.status}>{job.status}</Badge>
                    {job.status === 'failed' && (
                        <Button size="sm" onClick={handleRetry} disabled={actionLoading}>Retry</Button>
                    )}
                    {job.is_cancellable && (
                        <Button size="sm" variant="destructive" onClick={handleCancel} disabled={actionLoading}>Cancel</Button>
                    )}
                </div>
            </div>

            <Card>
                <CardHeader><CardTitle>Metadata</CardTitle></CardHeader>
                <CardContent>
                    <dl className="grid grid-cols-2 gap-x-8 gap-y-3 text-sm md:grid-cols-3">
                        <MetaItem label="Class" value={<span className="font-mono text-xs">{job.class}</span>} />
                        <MetaItem label="Queue" value={job.queue ?? '—'} />
                        <MetaItem label="Connection" value={job.connection ?? '—'} />
                        <MetaItem label="Attempts" value={`${job.attempts}${job.max_attempts !== null ? ` / ${job.max_attempts}` : ''}`} />
                        <MetaItem label="Started" value={job.started_at ? formatRelativeTime(job.started_at) : '—'} />
                        <MetaItem label="Completed" value={job.completed_at ? formatRelativeTime(job.completed_at) : '—'} />
                        <MetaItem label="Duration" value={formatDuration(job.duration_ms)} />
                    </dl>
                </CardContent>
            </Card>

            {job.error_message && (
                <Card>
                    <CardHeader><CardTitle>Error</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-red-400">{job.error_message}</p>
                        {job.stack_trace && (
                            <div>
                                <button
                                    className="text-xs text-[var(--muted-foreground)] underline"
                                    onClick={() => { setStackTraceOpen(!stackTraceOpen); }}
                                >
                                    {stackTraceOpen ? 'Hide' : 'Show'} stack trace
                                </button>
                                {stackTraceOpen && (
                                    <ScrollArea className="mt-2 max-h-48 rounded bg-[var(--muted)] p-3">
                                        <pre className="font-mono text-xs text-[var(--foreground)] whitespace-pre-wrap">{job.stack_trace}</pre>
                                    </ScrollArea>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader><CardTitle>Logs {isRunning && <span className="text-xs text-amber-400 ml-2">● streaming</span>}</CardTitle></CardHeader>
                <CardContent>
                    <ScrollArea className="max-h-64 rounded bg-[var(--muted)] p-3">
                        {allLogs.length === 0 ? (
                            <p className="font-mono text-xs text-[var(--muted-foreground)]">No log entries.</p>
                        ) : (
                            <div className="space-y-1">
                                {allLogs.map((log) => (
                                    <div key={log.id} className="flex items-start gap-2 font-mono text-xs">
                                        <Badge variant="status" status={log.level === 'error' ? 'failed' : log.level === 'warning' ? 'running' : 'completed'} className="shrink-0 mt-0.5">
                                            {log.level}
                                        </Badge>
                                        <span className="flex-1 text-[var(--foreground)]">{log.message}</span>
                                        <span className="shrink-0 text-[var(--muted-foreground)]">{formatRelativeTime(log.logged_at)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </ScrollArea>
                </CardContent>
            </Card>
        </div>
    );
}

function MetaItem({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-[var(--muted-foreground)] uppercase tracking-wider">{label}</dt>
            <dd className="mt-1 text-sm">{value}</dd>
        </div>
    );
}
