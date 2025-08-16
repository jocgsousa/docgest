import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import styled, { ThemeProvider, createGlobalStyle } from 'styled-components';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Users from './pages/Users';
import Companies from './pages/Companies';
import Documents from './pages/Documents';
import Signatures from './pages/Signatures';
import Plans from './pages/Plans';
import Reports from './pages/Reports';
import Settings from './pages/Settings';
import Logs from './pages/Logs';
import Branches from './pages/Branches';
import WhatsApp from './pages/WhatsApp';
import Profile from './pages/Profile';
import Usage from './pages/Usage';
import Layout from './components/layout/Layout';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { DataProvider } from './contexts/DataContext';

const theme = {
  colors: {
    primary: '#1a1a1a',
    primaryHover: '#333333',
    secondary: '#6b7280',
    success: '#059669',
    danger: '#dc2626',
    warning: '#d97706',
    light: '#fafafa',
    dark: '#0a0a0a',
    white: '#ffffff',
    text: '#111827',
    textSecondary: '#6b7280',
    background: '#ffffff',
    border: '#e5e7eb',
    gray: {
      25: '#fcfcfc',
      50: '#f9fafb',
      100: '#f3f4f6',
      200: '#e5e7eb',
      300: '#d1d5db',
      400: '#9ca3af',
      500: '#6b7280',
      600: '#4b5563',
      700: '#374151',
      800: '#1f2937',
      900: '#111827',
      950: '#030712'
    }
  },
  fonts: {
    primary: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
  },
  breakpoints: {
    mobile: '768px',
    tablet: '1024px',
    desktop: '1200px'
  }
};

const GlobalStyle = createGlobalStyle`
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: ${props => props.theme.fonts.primary};
    background-color: ${props => props.theme.colors.background};
    color: ${props => props.theme.colors.text};
    line-height: 1.6;
  }

  button {
    cursor: pointer;
    border: none;
    outline: none;
    font-family: inherit;
  }

  input, textarea, select {
    font-family: inherit;
    outline: none;
  }

  a {
    text-decoration: none;
    color: inherit;
  }

  ul, ol {
    list-style: none;
  }
`;

const AppContainer = styled.div`
  min-height: 100vh;
  display: flex;
  flex-direction: column;
`;

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();
  
  if (loading) {
    return <div>Carregando...</div>;
  }
  
  return user ? children : <Navigate to="/login" />;
}

function PublicRoute({ children }) {
  const { user, loading } = useAuth();
  
  if (loading) {
    return <div>Carregando...</div>;
  }
  
  return user ? <Navigate to="/dashboard" /> : children;
}

function App() {
  return (
    <ThemeProvider theme={theme}>
      <GlobalStyle />
      <AuthProvider>
        <DataProvider>
           <Router>
             <AppContainer>
               <Routes>
              <Route 
                path="/login" 
                element={
                  <PublicRoute>
                    <Login />
                  </PublicRoute>
                } 
              />
              <Route 
                path="/dashboard" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Dashboard />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/documentos" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Documents />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/usuarios" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Users />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/usuarios/novo" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Users openCreateModal={true} />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/empresas" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Companies />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/empresas/nova" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Companies openCreateModal={true} />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/assinaturas" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Signatures />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/planos" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Plans />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/relatorios" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Reports />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/configuracoes" 
                element={
                  <ProtectedRoute>
                    <Layout>
                      <Settings />
                    </Layout>
                  </ProtectedRoute>
                } 
              />
              <Route path="/logs" element={
              <ProtectedRoute>
                <Layout>
                  <Logs />
                </Layout>
              </ProtectedRoute>
            } />
            <Route path="/filiais" element={
              <ProtectedRoute>
                <Layout>
                  <Branches />
                </Layout>
              </ProtectedRoute>
            } />
            <Route path="/whatsapp" element={
              <ProtectedRoute>
                <Layout>
                  <WhatsApp />
                </Layout>
              </ProtectedRoute>
            } />
            <Route path="/perfil" element={
              <ProtectedRoute>
                <Layout>
                  <Profile />
                </Layout>
              </ProtectedRoute>
            } />
            <Route path="/uso-mensal" element={
              <ProtectedRoute>
                <Layout>
                  <Usage />
                </Layout>
              </ProtectedRoute>
            } />
              <Route path="/" element={<Navigate to="/dashboard" />} />
            </Routes>
             </AppContainer>
           </Router>
        </DataProvider>
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;