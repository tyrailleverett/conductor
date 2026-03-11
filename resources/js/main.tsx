import { createRoot } from 'react-dom/client';
import './app.css';
import App from '@/components/App';

const container = document.getElementById('app')!;
createRoot(container).render(<App />);
