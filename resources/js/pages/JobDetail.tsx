import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { apiGet, apiPost, apiDelete } from '@/lib/api';
import { formatDuration } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';
import { useLogStream } from '@/hooks/useLogStream';
import { ArrowLeft, RotateCcw, XCircle, Terminal } from 'lucide-react';
import type { ConductorJob, ConductorJobLog } from '@/types';

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : status === 'running' ? 'bg-amber-400 text-amber-400'
        : 'bg-zinc-500 text-zinc-400';
    return (
        <span className="flex items-center gap-1.5 text-sm">
            <span className={`inline-block h-2 w-2 rounded-full ${color.split(' ')[0]}`} />
            <span className={`capitalize font-medium ${color.split(' ')[1]}`}>{status}</span>
        </span>
    );
}

function formatDateTime(iso: string | null): string {
    if (!iso) { return '—'; }
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}

function MetaCard({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] px-4 py-3">
            <div className="text-[10px] font-medium uppercase tracking-wider text-[var(--muted-foreground)] mb-1">{label}</div>
            <div className="text-sm text-[var(--foreground)] font-mono">{children}</div>
        </div>
    );
}

function levelColor(level: string): string {
    if (level === 'error') { return 'text-red-400'; }
    if (level === 'warning') { return 'text-amber-400'; }
    if (level === 'debug') { return 'text-zinc-500'; }
    return 'text-[var(--muted-foreground)]';
}

export default function JobDetail() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [job, setJob] = useState<ConductorJob | null>(null);
    const [loading, setLoading] = useState(true);
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
                <Skeleton className="h-5 w-32" />
                <Skeleton className="h-8 w-64" />
                <div className="grid grid-cols-4 gap-3">
                    {[...Array(8)].map((_, i) => <Skeleton key={i} className="h-16 rounded-lg" />)}
                </div>
                <Skeleton className="h-64 rounded-lg" />
            </div>
        );
    }

    if (!job) {
        return <p className="text-[var(--muted-foreground)]">Job not found.</p>;
    }

    return (
        <div className="space-y-6">
            {/* Back link */}
            <button
                className="flex items-center gap-1.5 text-sm text-[var(--muted-foreground)] hover:text-[var(--foreground)] transition-colors font-mono"
                onClick={() => { navigate('/jobs'); }}
            >
                <ArrowLeft className="h-3.5 w-3.5" />
                All Jobs
            </button>

            {/* Header */}
            <div className="flex items-start justify-between">
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="font-mono text-2xl font-bold text-[var(--foreground)]">{job.display_name}</h1>
                        <StatusDot status={job.status} />
                    </div>
                    <p className="mt-1 font-mono text-xs text-[var(--muted-foreground)]">{job.id}</p>
                    {job.tags.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {job.tags.map((tag) => (
                                <span key={tag} className="rounded border border-[var(--border)] bg-[var(--muted)] px-2 py-0.5 font-mono text-xs text-[var(--muted-foreground)]">
                                    {tag}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {job.status === 'failed' && (
                        <button
                            onClick={handleRetry}
                            disabled={actionLoading}
                            className="flex items-center gap-1.5 rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] hover:bg-[var(--muted)] transition-colors disabled:opacity-50"
                        >
                            <RotateCcw className="h-3.5 w-3.5" />
                            Retry
                        </button>
                    )}
                    {job.is_cancellable && (
                        <button
                            onClick={handleCancel}
                            disabled={actionLoading}
                            className="flex items-center gap-1.5 rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] hover:bg-[var(--muted)] transition-colors disabled:opacity-50"
                        >
                            <XCircle className="h-3.5 w-3.5" />
                            Cancel
                        </button>
                    )}
                </div>
            </div>

            {/* Metadata grid — 4 columns, 2 rows */}
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <MetaCard label="Job Class">{job.class}</MetaCard>
                <MetaCard label="Queue">{job.queue ?? '—'}</MetaCard>
                <MetaCard label="Connection">{job.connection ?? '—'}</MetaCard>
                <MetaCard label="Attempts">{job.attempts}{job.max_attempts !== null ? ` / ${job.max_attempts}` : ''}</MetaCard>
                <MetaCard label="Duration">{formatDuration(job.duration_ms)}</MetaCard>
                <MetaCard label="Created">{formatDateTime(job.created_at)}</MetaCard>
                <MetaCard label="Started">{formatDateTime(job.started_at)}</MetaCard>
                <MetaCard label="Completed">{formatDateTime(job.completed_at)}</MetaCard>
            </div>

            {/* Error */}
            {job.error_message && (
                <div className="rounded-lg border border-red-400/30 bg-red-400/5 px-4 py-3">
                    <p className="text-sm text-red-400">{job.error_message}</p>
                </div>
            )}

            {/* Log Output */}
            <div>
                <div className="flex items-center gap-2 mb-2">
                    <Terminal className="h-4 w-4 text-[var(--muted-foreground)]" />
                    <span className="text-sm font-medium text-[var(--foreground)]">Log Output</span>
                    {isRunning && <span className="text-xs text-amber-400">● streaming</span>}
                </div>
                <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] p-4 font-mono text-xs overflow-auto max-h-80">
                    {allLogs.length === 0 ? (
                        <p className="text-[var(--muted-foreground)]">No log entries.</p>
                    ) : (
                        <div className="space-y-1">
                            {allLogs.map((log) => {
                                const d = new Date(log.logged_at);
                                const time = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                                return (
                                    <div key={log.id} className="flex items-start gap-3">
                                        <span className="shrink-0 text-[var(--muted-foreground)]">{time}</span>
                                        <span className={`shrink-0 w-10 ${levelColor(log.level)}`}>{log.level.toUpperCase()}</span>
                                        <span className="text-[var(--foreground)]">{log.message}</span>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
