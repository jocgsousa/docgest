import React, { useState } from 'react';
import styled from 'styled-components';
import { useAuth } from '../../contexts/AuthContext';
import Sidebar from './Sidebar';
import Header from './Header';
import DataLoadingIndicator from '../DataLoadingIndicator';

const LayoutContainer = styled.div`
  display: flex;
  min-height: 100vh;
  background-color: ${props => props.theme.colors.gray[50]};
`;

const MainContent = styled.main`
  flex: 1;
  display: flex;
  flex-direction: column;
  transition: margin-left 0.3s ease-in-out;
  margin-left: ${props => props.sidebarCollapsed ? '4rem' : '16rem'};
  
  @media (max-width: 768px) {
    margin-left: 0;
  }
`;

const ContentArea = styled.div`
  flex: 1;
  padding: 1.5rem;
  
  @media (max-width: 768px) {
    padding: 1rem;
  }
`;

const MobileOverlay = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 40;
  display: none;
  
  @media (max-width: 768px) {
    display: ${props => props.show ? 'block' : 'none'};
  }
`;

function Layout({ children }) {
  const { user } = useAuth();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
  
  const toggleSidebar = () => {
    setSidebarCollapsed(!sidebarCollapsed);
  };
  
  const toggleMobileSidebar = () => {
    setMobileSidebarOpen(!mobileSidebarOpen);
  };
  
  const closeMobileSidebar = () => {
    setMobileSidebarOpen(false);
  };
  
  if (!user) {
    return children;
  }
  
  return (
    <LayoutContainer>
      <Sidebar 
        collapsed={sidebarCollapsed}
        mobileOpen={mobileSidebarOpen}
        onClose={closeMobileSidebar}
      />
      
      <MobileOverlay 
        show={mobileSidebarOpen} 
        onClick={closeMobileSidebar} 
      />
      
      <MainContent sidebarCollapsed={sidebarCollapsed}>
        <Header 
          onToggleSidebar={toggleSidebar}
          onToggleMobileSidebar={toggleMobileSidebar}
          sidebarCollapsed={sidebarCollapsed}
        />
        
        <ContentArea>
          {children}
        </ContentArea>
      </MainContent>
      
      <DataLoadingIndicator />
    </LayoutContainer>
  );
}

export default Layout;