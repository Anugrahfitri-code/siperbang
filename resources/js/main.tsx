declare const __FRONTEND_SOURCE_HASH__: string;

import {StrictMode} from 'react';
import {createRoot} from 'react-dom/client';
import App from './App';
import { SettingsProvider } from './shared/context/SettingsContext';
import '../css/app.css';

const buildMeta = document.createElement('meta');
buildMeta.name = 'siperbang-build-source';
buildMeta.content = __FRONTEND_SOURCE_HASH__;
document.head.appendChild(buildMeta);

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <SettingsProvider>
      <App />
    </SettingsProvider>
  </StrictMode>,
);
