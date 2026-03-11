import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { SimpleTabs } from '@/components/ui/tabs';
import { Skeleton } from '@/components/ui/skeleton';
import {
    LineChart,
    Line,
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import type { MetricsResponse, MetricPoint } from '@/types';

const WINDOW_TABS = [
    { value: '1h', label: '1h' },
    { value: '24h', label: '24h' },
    { value: '7d', label: '7d' },
];

function formatTime(iso: string, window: string): string {
    const d = new Date(iso);
    if (window === '7d') {
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

export default function Metrics() {
    const [window, setWindow] = useState<'1h' | '24h' | '7d'>('1h');
    const [metrics, setMetrics] = useState<MetricsResponse | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        apiGet<MetricsResponse>(`/metrics?window=${window}`)
            .then((res) => { setMetrics(res); })
            .finally(() => { setLoading(false); });
    }, [window]);

    const throughputData = (metrics?.throughput ?? []).map((p: MetricPoint) => ({
        time: formatTime(p.recorded_at, window),
        value: p.value,
    }));

    const failureData = (metrics?.failure_rate ?? []).map((p: MetricPoint) => ({
        time: formatTime(p.recorded_at, window),
        value: +(p.value * 100).toFixed(1),
    }));

    const queueKeys = Object.keys(metrics?.queue_depth ?? {});
    const queueData = queueKeys.length > 0
        ? (metrics?.queue_depth[queueKeys[0]] ?? []).map((p: MetricPoint, i: number) => {
            const point: Record<string, unknown> = { time: formatTime(p.recorded_at, window) };
            queueKeys.forEach((q) => {
                point[q] = metrics?.queue_depth[q]?.[i]?.value ?? 0;
            });
            return point;
        })
        : [];

    const COLORS = ['#4ade80', '#60a5fa', '#f59e0b', '#f87171', '#a78bfa'];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-lg font-semibold">Metrics</h1>
                    <p className="text-sm text-[var(--muted-foreground)]">Job throughput and queue health</p>
                </div>
                <SimpleTabs
                    tabs={WINDOW_TABS}
                    value={window}
                    onValueChange={(v) => { setWindow(v as '1h' | '24h' | '7d'); }}
                />
            </div>

            {loading ? (
                <div className="space-y-4">
                    <Skeleton className="h-48 rounded-lg" />
                    <Skeleton className="h-48 rounded-lg" />
                    <Skeleton className="h-48 rounded-lg" />
                </div>
            ) : (
                <div className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>Throughput (jobs completed)</CardTitle></CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <LineChart data={throughputData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                    <XAxis dataKey="time" tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                    <YAxis tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                    <Tooltip contentStyle={{ background: 'var(--popover)', border: '1px solid var(--border)', color: 'var(--foreground)' }} />
                                    <Line type="monotone" dataKey="value" stroke="#4ade80" strokeWidth={2} dot={false} name="Jobs" />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Failure Rate (%)</CardTitle></CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <LineChart data={failureData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                    <XAxis dataKey="time" tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                    <YAxis tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                    <Tooltip contentStyle={{ background: 'var(--popover)', border: '1px solid var(--border)', color: 'var(--foreground)' }} />
                                    <Line type="monotone" dataKey="value" stroke="#f87171" strokeWidth={2} dot={false} name="Failure %" />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {queueKeys.length > 0 && (
                        <Card>
                            <CardHeader><CardTitle>Queue Depth (pending jobs)</CardTitle></CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={200}>
                                    <AreaChart data={queueData}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                        <XAxis dataKey="time" tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                        <YAxis tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }} />
                                        <Tooltip contentStyle={{ background: 'var(--popover)', border: '1px solid var(--border)', color: 'var(--foreground)' }} />
                                        <Legend />
                                        {queueKeys.map((q, i) => (
                                            <Area
                                                key={q}
                                                type="monotone"
                                                dataKey={q}
                                                stackId="1"
                                                stroke={COLORS[i % COLORS.length]}
                                                fill={`${COLORS[i % COLORS.length]}33`}
                                            />
                                        ))}
                                    </AreaChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    )}
                </div>
            )}
        </div>
    );
}
