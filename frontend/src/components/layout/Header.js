import React, { useState } from 'react';
import styled from 'styled-components';
import { useAuth } from '../../contexts/AuthContext';
import Button from '../common/Button';
import { 
  Menu, 
  ChevronLeft, 
  ChevronRight, 
  Bell, 
  User, 
  Settings, 
  LogOut 
} from 'lucide-react';

const HeaderContainer = styled.header`
  background-color: ${props => props.theme.colors.white};
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  padding: 0 1.5rem;
  height: 4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 30;
`;

const LeftSection = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
`;

const MenuButton = styled.button.withConfig({
  shouldForwardProp: (prop) => !['hideOnDesktop', 'hideOnMobile'].includes(prop)
})`
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 0.375rem;
  color: ${props => props.theme.colors.gray[600]};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
    color: ${props => props.theme.colors.gray[900]};
  }
  
  @media (min-width: 769px) {
    display: ${props => props.hideOnDesktop ? 'none' : 'block'};
  }
  
  @media (max-width: 768px) {
    display: ${props => props.hideOnMobile ? 'none' : 'block'};
  }
`;

const PageTitle = styled.h1`
  font-size: 1.5rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0;
  
  @media (max-width: 640px) {
    font-size: 1.25rem;
  }
`;

const RightSection = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
`;

const UserMenu = styled.div`
  position: relative;
`;

const UserButton = styled.button`
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border: none;
  background: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
  }
`;

const UserAvatar = styled.div`
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background-color: ${props => props.theme.colors.primary};
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.white};
  font-weight: 600;
  font-size: 0.875rem;
`;

const UserInfo = styled.div`
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  
  @media (max-width: 640px) {
    display: none;
  }
`;

const UserName = styled.span`
  font-weight: 500;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[900]};
`;

const UserRole = styled.span`
  font-size: 0.75rem;
  color: ${props => props.theme.colors.gray[500]};
`;

const DropdownMenu = styled.div`
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  min-width: 12rem;
  z-index: 50;
  display: ${props => props.show ? 'block' : 'none'};
`;

const DropdownItem = styled.button`
  width: 100%;
  padding: 0.75rem 1rem;
  text-align: left;
  border: none;
  background: none;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[700]};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[50]};
    color: ${props => props.theme.colors.gray[900]};
  }
  
  &:first-child {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }
  
  &:last-child {
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
  }
`;

const NotificationBadge = styled.div`
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background-color: ${props => props.theme.colors.danger};
    border-radius: 50%;
    display: ${props => props.show ? 'block' : 'none'};
  }
`;

function Header({ 
  onToggleSidebar, 
  onToggleMobileSidebar, 
  sidebarCollapsed,
  title = 'DocGest'
}) {
  const { user, logout } = useAuth();
  const [showUserMenu, setShowUserMenu] = useState(false);
  
  const getUserInitials = (name) => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };
  
  const getRoleLabel = (tipoUsuario) => {
    switch (tipoUsuario) {
      case 1:
        return 'Super Admin';
      case 2:
        return 'Admin Empresa';
      case 3:
        return 'Assinante';
      default:
        return 'Usuário';
    }
  };
  
  const handleLogout = () => {
    logout();
    setShowUserMenu(false);
  };
  
  return (
    <HeaderContainer>
      <LeftSection>
        <MenuButton 
          onClick={onToggleMobileSidebar}
          hideOnDesktop
        >
          <Menu size={20} />
        </MenuButton>
        
        <MenuButton 
          onClick={onToggleSidebar}
          hideOnMobile
        >
          {sidebarCollapsed ? <ChevronRight size={20} /> : <ChevronLeft size={20} />}
        </MenuButton>
        
        <PageTitle>{title}</PageTitle>
      </LeftSection>
      
      <RightSection>
        <NotificationBadge show={false}>
          <MenuButton>
            <Bell size={20} />
          </MenuButton>
        </NotificationBadge>
        
        <UserMenu>
          <UserButton 
            onClick={() => setShowUserMenu(!showUserMenu)}
          >
            <UserAvatar>
              {getUserInitials(user?.nome || 'U')}
            </UserAvatar>
            <UserInfo>
              <UserName>{user?.nome}</UserName>
              <UserRole>{getRoleLabel(user?.tipo_usuario)}</UserRole>
            </UserInfo>
            <span style={{ fontSize: '0.75rem', color: '#9CA3AF' }}>▼</span>
          </UserButton>
          
          <DropdownMenu show={showUserMenu}>
            <DropdownItem onClick={() => setShowUserMenu(false)}>
              <User size={16} style={{ marginRight: '8px' }} /> Meu Perfil
            </DropdownItem>
            <DropdownItem onClick={() => setShowUserMenu(false)}>
              <Settings size={16} style={{ marginRight: '8px' }} /> Configurações
            </DropdownItem>
            <DropdownItem onClick={handleLogout}>
              <LogOut size={16} style={{ marginRight: '8px' }} /> Sair
            </DropdownItem>
          </DropdownMenu>
        </UserMenu>
      </RightSection>
    </HeaderContainer>
  );
}

export default Header;