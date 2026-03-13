import React, { useEffect, useMemo, useState } from 'react';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface ConductorWebhookSource {
    id: string;
    source: string;
    function_class: string;
    is_active: boolean;
    logs_count?: number;
}

interface ConductorWebhookLog {
    id: number;
    source: string;
    payload: Record<string, unknown>;
    masked_signature: string | null;
    status: 'received' | 'verified' | 'processed' | 'failed';
    received_at: string | null;
}

interface WebhookResponse {
    data: {
        sources: ConductorWebhookSource[];
        logs: ConductorWebhookLog[];
    };
}

export default function Webhooks() {
    const [sources, setSources] = useState<ConductorWebhookSource[]>([]);
    const [logs, setLogs] = useState<ConductorWebhookLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [sourceFilter, setSourceFilter] = useState('');

    useEffect(() => {
        async function fetchWebhooks() {
            setLoading(true);

            try {
                const query = sourceFilter ? `?source=${encodeURIComponent(sourceFilter)}` : '';
                const response = await apiGet<WebhookResponse>(`/webhooks${query}`);
                setSources(response.data.sources);
                setLogs(response.data.logs);
            } finally {
                setLoading(false);
            }
        }

        fetchWebhooks();
    }, [sourceFilter]);

    const totalLogs = useMemo(
        () => sources.reduce((count, source) => count + (source.logs_count ?? 0), 0),
        [sources],
    );

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 className="text-lg font-semibold">Webhooks</h1>
                    <p className="text-sm text-[var(--muted-foreground)]">
                        Registered sources and the latest inbound requests.
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <select
                        className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)]"
                        value={sourceFilter}
                        onChange={(event) => {
                            setSourceFilter(event.target.value);
                        }}
                    >
                        <option value="">All sources</option>
                        {sources.map((source) => (
                            <option key={source.id} value={source.source}>
                                {source.source}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <MetricCard title="Sources" value={sources.length} />
                <MetricCard title="Logged Requests" value={totalLogs} />
                <MetricCard title="Visible Entries" value={logs.length} />
            </div>

            <div className="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Webhook Sources</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {loading ? (
                            <div className="space-y-2 p-4">
                                {[...Array(3)].map((_, index) => (
                                    <Skeleton key={index} className="h-16 rounded" />
                                ))}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Source</TableHead>
                                        <TableHead>Function Class</TableHead>
                                        <TableHead>Active</TableHead>
                                        <TableHead>Logs</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sources.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-center text-[var(--muted-foreground)]">
                                                No webhook sources registered.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {sources.map((source) => (
                                        <TableRow key={source.id}>
                                            <TableCell className="font-mono text-xs">{source.source}</TableCell>
                                            <TableCell className="font-mono text-xs text-[var(--muted-foreground)]">
                                                {source.function_class}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="status" status={source.is_active ? 'completed' : 'failed'}>
                                                    {source.is_active ? 'active' : 'inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{source.logs_count ?? 0}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Deliveries</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {loading ? (
                            <div className="space-y-2">
                                {[...Array(4)].map((_, index) => (
                                    <Skeleton key={index} className="h-24 rounded" />
                                ))}
                            </div>
                        ) : logs.length === 0 ? (
                            <p className="text-sm text-[var(--muted-foreground)]">No webhook deliveries found.</p>
                        ) : (
                            logs.map((log) => (
                                <div key={log.id} className="rounded-lg border border-[var(--border)] p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <span className="font-mono text-xs text-[var(--foreground)]">{log.source}</span>
                                            <Badge variant="status" status={mapWebhookStatus(log.status)}>
                                                {log.status}
                                            </Badge>
                                        </div>
                                        <span className="text-xs text-[var(--muted-foreground)]">
                                            {log.received_at ? formatRelativeTime(log.received_at) : '—'}
                                        </span>
                                    </div>
                                    <div className="mt-3 grid gap-3 md:grid-cols-[0.9fr_1.1fr]">
                                        <div className="rounded-md bg-[var(--muted)] p-3">
                                            <div className="text-[11px] uppercase tracking-wider text-[var(--muted-foreground)]">
                                                Signature
                                            </div>
                                            <div className="mt-2 font-mono text-xs text-[var(--foreground)]">
                                                {log.masked_signature ?? '—'}
                                            </div>
                                        </div>
                                        <pre className="max-h-40 overflow-auto rounded-md bg-[var(--muted)] p-3 font-mono text-xs text-[var(--foreground)] whitespace-pre-wrap">
                                            {JSON.stringify(log.payload, null, 2)}
                                        </pre>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function MetricCard({ title, value }: { title: string; value: number }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="text-3xl font-semibold">{value}</div>
            </CardContent>
        </Card>
    );
}

function mapWebhookStatus(status: ConductorWebhookLog['status']): 'pending' | 'running' | 'completed' | 'failed' {
    return status === 'failed' ? 'failed' : status === 'received' ? 'pending' : status === 'verified' ? 'running' : 'completed';
}
