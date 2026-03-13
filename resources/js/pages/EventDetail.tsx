import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { Skeleton } from '@/components/ui/skeleton';
import { ArrowLeft } from 'lucide-react';
import type { ConductorEvent } from '@/types';

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : status === 'running' ? 'bg-amber-400 text-amber-400'
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
    return 'Dispatched ' + d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}

export default function EventDetail() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
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
                <Skeleton className="h-6 w-32" />
                <Skeleton className="h-10 w-72" />
                <Skeleton className="h-40 rounded-lg" />
                <Skeleton className="h-32 rounded-lg" />
            </div>
        );
    }

    if (!event) {
        return <p className="text-[var(--muted-foreground)]">Event not found.</p>;
    }

    return (
        <div className="space-y-6">
            {/* Back link */}
            <button
                className="flex items-center gap-1.5 text-sm text-[var(--muted-foreground)] hover:text-[var(--foreground)] transition-colors"
                onClick={() => { navigate('/events'); }}
            >
                <ArrowLeft className="h-3.5 w-3.5" />
                All Events
            </button>

            {/* Title */}
            <div>
                <h1 className="font-mono text-2xl font-semibold text-[var(--foreground)]">{event.name}</h1>
                <p className="mt-1 font-mono text-sm text-[var(--muted-foreground)]">{formatDateTime(event.dispatched_at)}</p>
            </div>

            {/* Payload */}
            <div className="space-y-2">
                <h2 className="text-base font-semibold text-[var(--foreground)]">Payload</h2>
                <pre className="rounded-lg border border-[var(--border)] bg-[var(--card)] p-4 font-mono text-sm text-[var(--foreground)] overflow-auto max-h-72">
                    {JSON.stringify(event.payload, null, 2)}
                </pre>
            </div>

            {/* Triggered Runs */}
            <div className="space-y-2">
                <h2 className="text-base font-semibold text-[var(--foreground)]">Triggered Runs</h2>
                <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--border)]">
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Run</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(event.runs ?? []).length === 0 && (
                                <tr>
                                    <td colSpan={2} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No runs.</td>
                                </tr>
                            )}
                            {(event.runs ?? []).map((run) => (
                                <tr key={run.id} className="border-b border-[var(--border)] last:border-0">
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--foreground)]">{run.function_class}</td>
                                    <td className="px-4 py-3"><StatusDot status={run.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
