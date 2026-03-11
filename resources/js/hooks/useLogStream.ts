import { useEffect, useRef, useState } from 'react';
import { apiUrl, apiGet } from '@/lib/api';
import type { ConductorJobLog, ConductorJob } from '@/types';

function appendUniqueLogs(existingLogs: ConductorJobLog[], nextLogs: ConductorJobLog[]): ConductorJobLog[] {
    const mergedLogs = new Map<number, ConductorJobLog>();

    existingLogs.forEach((log) => {
        mergedLogs.set(log.id, log);
    });

    nextLogs.forEach((log) => {
        mergedLogs.set(log.id, log);
    });

    return [...mergedLogs.values()].sort((leftLog, rightLog) => leftLog.id - rightLog.id);
}

export function useLogStream(jobId: string, isRunning: boolean): ConductorJobLog[] {
    const [logs, setLogs] = useState<ConductorJobLog[]>([]);
    const sourceRef = useRef<EventSource | null>(null);
    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        if (!isRunning || !jobId) {
            setLogs([]);

            return;
        }

        const url = apiUrl(`/jobs/${jobId}/stream`);
        const source = new EventSource(url);
        sourceRef.current = source;

        source.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data) as { event?: string } & ConductorJobLog;
                if (data.event === 'done') {
                    source.close();
                    return;
                }
                setLogs((previousLogs) => appendUniqueLogs(previousLogs, [data as ConductorJobLog]));
            } catch {
            }
        };

        source.onerror = () => {
            source.close();
            sourceRef.current = null;

            pollRef.current = setInterval(async () => {
                try {
                    const res = await apiGet<{ data: ConductorJob }>(`/jobs/${jobId}`);
                    const newLogs = res.data.logs ?? [];
                    setLogs((previousLogs) => appendUniqueLogs(previousLogs, newLogs));

                    if (res.data.status !== 'running') {
                        if (pollRef.current !== null) {
                            clearInterval(pollRef.current);
                            pollRef.current = null;
                        }
                    }
                } catch {
                }
            }, 2000);
        };

        return () => {
            source.close();
            if (pollRef.current !== null) {
                clearInterval(pollRef.current);
                pollRef.current = null;
            }
        };
    }, [jobId, isRunning]);

    return logs;
}
