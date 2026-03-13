import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router';
import Layout from '@/components/Layout';
import Overview from '@/pages/Overview';
import Jobs from '@/pages/Jobs';
import JobDetail from '@/pages/JobDetail';
import Workflows from '@/pages/Workflows';
import WorkflowDetail from '@/pages/WorkflowDetail';
import Events from '@/pages/Events';
import EventDetail from '@/pages/EventDetail';
import Webhooks from '@/pages/Webhooks';
import Schedules from '@/pages/Schedules';
import Metrics from '@/pages/Metrics';
import Queues from '@/pages/Queues';

export default function App() {
    const basePath = window.__conductor__?.basePath ?? 'conductor';

    return (
        <BrowserRouter basename={`/${basePath}`}>
            <Routes>
                <Route element={<Layout />}>
                    <Route index element={<Overview />} />
                    <Route path="jobs" element={<Jobs />} />
                    <Route path="jobs/:id" element={<JobDetail />} />
                    <Route path="workflows" element={<Workflows />} />
                    <Route path="workflows/:id" element={<WorkflowDetail />} />
                    <Route path="events" element={<Events />} />
                    <Route path="events/:id" element={<EventDetail />} />
                    <Route path="webhooks" element={<Webhooks />} />
                    <Route path="schedules" element={<Schedules />} />
                    <Route path="metrics" element={<Metrics />} />
                    <Route path="queues" element={<Queues />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
