import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
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

const WINDOW_OPTIONS = ['1h', '24h', '7d'] as const;
type WindowOption = typeof WINDOW_OPTIONS[number];

function formatTime(iso: string, window: WindowOption): string {
    const d = new Date(iso);
    if (window === '7d') {
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

const COLORS = ['#4ade80', '#60a5fa', '#f59e0b', '#f87171', '#a78bfa'];

function ChartPanel({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] p-5">
            <p className="mb-4 text-sm font-medium text-[var(--foreground)]">{title}</p>
            {children}
        </div>
    );
}

export default function Metrics() {
    const [window, setWindow] = useState<WindowOption>('1h');
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

    const tickStyle = { fill: 'var(--muted-foreground)', fontSize: 11 };
    const tooltipStyle = { background: 'var(--popover)', border: '1px solid var(--border)', color: 'var(--foreground)', fontSize: 12 };

    return (
        <div className="space-y-5">
            <div className="flex items-start justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-[var(--foreground)]">Metrics</h1>
                    <p className="text-sm text-[var(--muted-foreground)]">System performance over time</p>
                </div>
                {/* Segmented window picker */}
                <div className="flex items-center rounded-md border border-[var(--border)] overflow-hidden">
                    {WINDOW_OPTIONS.map((opt) => (
                        <button
                            key={opt}
                            className={`px-3 py-1.5 text-sm font-mono transition-colors ${
                                window === opt
                                    ? 'bg-[var(--foreground)] text-[var(--background)]'
                                    : 'bg-[var(--card)] text-[var(--muted-foreground)] hover:text-[var(--foreground)]'
                            }`}
                            onClick={() => { setWindow(opt); }}
                        >
                            {opt}
                        </button>
                    ))}
                </div>
            </div>

            {loading ? (
                <div className="space-y-4">
                    <Skeleton className="h-56 rounded-lg" />
                    <Skeleton className="h-56 rounded-lg" />
                    <Skeleton className="h-56 rounded-lg" />
                </div>
            ) : (
                <div className="space-y-5">
                    <ChartPanel title="Throughput (jobs / min)">
                        <ResponsiveContainer width="100%" height={220}>
                            <LineChart data={throughputData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                <XAxis dataKey="time" tick={tickStyle} />
                                <YAxis tick={tickStyle} />
                                <Tooltip contentStyle={tooltipStyle} />
                                <Line type="monotone" dataKey="value" stroke="#4ade80" strokeWidth={2} dot={false} name="Jobs" />
                            </LineChart>
                        </ResponsiveContainer>
                    </ChartPanel>

                    <ChartPanel title="Failure Rate (%)">
                        <ResponsiveContainer width="100%" height={220}>
                            <LineChart data={failureData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                <XAxis dataKey="time" tick={tickStyle} />
                                <YAxis tick={tickStyle} />
                                <Tooltip contentStyle={tooltipStyle} />
                                <Line type="monotone" dataKey="value" stroke="#f87171" strokeWidth={2} dot={false} name="Failure %" />
                            </LineChart>
                        </ResponsiveContainer>
                    </ChartPanel>

                    {queueKeys.length > 0 && (
                        <ChartPanel title="Queue Depth">
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={queueData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                                    <XAxis dataKey="time" tick={tickStyle} />
                                    <YAxis tick={tickStyle} />
                                    <Tooltip contentStyle={tooltipStyle} />
                                    <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
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
                        </ChartPanel>
                    )}
                </div>
            )}
        </div>
    );
}
