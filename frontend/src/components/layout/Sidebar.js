import React from 'react';
import styled, { css } from 'styled-components';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useApp } from '../../contexts/AppContext';
import {
  BarChart3,
  Building2,
  Gem,
  Users,
  Settings,
  FileText,
  PenTool,
  TrendingUp,
  ClipboardList,
  Store,
  MessageCircle,
  User,
  Briefcase,
  Bell
} from 'lucide-react';

const SidebarContainer = styled.aside.withConfig({
  shouldForwardProp: (prop) => !['collapsed', 'mobileOpen'].includes(prop)
})`
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: ${props => props.collapsed ? '4rem' : '16rem'};
  background-color: ${props => props.theme.colors.white};
  border-right: 1px solid ${props => props.theme.colors.gray[100]};
  transition: all 0.3s ease-in-out;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  
  @media (max-width: ${props => props.theme.breakpoints.mobile}) {
    transform: translateX(${props => props.mobileOpen ? '0' : '-100%'});
    width: 16rem;
  }
`;

const SidebarHeader = styled.div`
  padding: 1.5rem 1rem;
  border-bottom: 1px solid ${props => props.theme.colors.gray[100]};
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background-color: ${props => props.theme.colors.gray[25]};
`;

const Logo = styled.div`
  width: 2rem;
  height: 2rem;
  background-color: ${props => props.theme.colors.primary};
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.white};
  font-weight: 700;
  font-size: 1.125rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
`;

const LogoText = styled.span.withConfig({
  shouldForwardProp: (prop) => prop !== 'collapsed'
})`
  font-size: 1.25rem;
  font-weight: 700;
  color: ${props => props.theme.colors.primary};
  transition: opacity 0.3s ease-in-out;
  
  ${props => props.collapsed && css`
    opacity: 0;
    width: 0;
    overflow: hidden;
  `}
`;

const NavText = styled.span.withConfig({
  shouldForwardProp: (prop) => prop !== 'collapsed'
})`
  transition: opacity 0.3s ease-in-out;
  
  ${props => props.collapsed && css`
    opacity: 0;
    width: 0;
    overflow: hidden;
  `}
`;

const Navigation = styled.nav`
  padding: 1rem 0;
  flex: 1;
  overflow-y: auto;
`;

const NavSection = styled.div`
  margin-bottom: 1.5rem;
`;

const NavSectionTitle = styled.div.withConfig({
  shouldForwardProp: (prop) => prop !== 'collapsed'
})`
  font-size: 0.75rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[500]};
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.5rem 1rem;
  margin-top: 1rem;
  transition: opacity 0.3s ease-in-out;
  
  ${props => props.collapsed && css`
    opacity: 0;
    height: 0;
    padding: 0;
    margin: 0;
    overflow: hidden;
  `}
`;

const NavItem = styled(Link)`
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin: 0 0.5rem;
  color: ${props => props.theme.colors.gray[600]};
  text-decoration: none;
  transition: all 0.15s ease-in-out;
  position: relative;
  border-radius: 0.5rem;
  font-weight: 500;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[50]};
    color: ${props => props.theme.colors.gray[900]};
  }
  
  ${props => props.active && css`
    background-color: ${props.theme.colors.primary};
    color: ${props.theme.colors.white};
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    
    &:hover {
      background-color: ${props.theme.colors.primaryHover};
      color: ${props.theme.colors.white};
    }
  `}
`;

const NavIcon = styled.span`
  font-size: 1.25rem;
  flex-shrink: 0;
  width: 1.5rem;
  text-align: center;
`;



const SidebarFooter = styled.div`
  padding: 1rem;
  border-top: 1px solid ${props => props.theme.colors.gray[100]};
  background-color: ${props => props.theme.colors.gray[25]};
`;

const CompanyInfo = styled.div.withConfig({
  shouldForwardProp: (prop) => prop !== 'collapsed'
})`
  padding: 0.75rem;
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.5rem;
  transition: opacity 0.3s ease-in-out;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  
  ${props => props.collapsed && css`
    opacity: 0;
    height: 0;
    padding: 0;
    overflow: hidden;
  `}
`;

const CompanyName = styled.div`
  font-weight: 600;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.25rem;
`;

const CompanyPlan = styled.div`
  font-size: 0.75rem;
  color: ${props => props.theme.colors.gray[500]};
`;

function Sidebar({ collapsed, mobileOpen, onClose }) {
  const { user, isAdmin, isCompanyAdmin } = useAuth();
  const { appInfo } = useApp();
  const location = useLocation();
  
  const getMenuItems = () => {
    const commonItems = [
      {
        section: 'Principal',
        items: [
          { path: '/dashboard', icon: BarChart3, label: 'Dashboard' },
        ]
      }
    ];
    
    if (isAdmin) {
      return [
        ...commonItems,
        {
          section: 'Administração',
          items: [
            { path: '/empresas', icon: Building2, label: 'Empresas' },
            { path: '/planos', icon: Gem, label: 'Planos' },
            { path: '/usuarios', icon: Users, label: 'Usuários' },
            { path: '/profissoes', icon: Briefcase, label: 'Profissões' },
            { path: '/notificacoes', icon: Bell, label: 'Notificações' },
            { path: '/configuracoes', icon: Settings, label: 'Configurações' },
          ]
        },
        {
          section: 'Documentos',
          items: [
            { path: '/documentos', icon: FileText, label: 'Documentos' },
            { path: '/assinaturas', icon: PenTool, label: 'Assinaturas' },
          ]
        },
        {
          section: 'Relatórios',
          items: [
            { path: '/relatorios', icon: TrendingUp, label: 'Relatórios' },
            { path: '/logs', icon: ClipboardList, label: 'Logs' },
          ]
        },
        {
          section: 'Conta',
          items: [
            { path: '/perfil', icon: User, label: 'Meu Perfil' },
          ]
        }
      ];
    }
    
    if (isCompanyAdmin) {
      return [
        ...commonItems,
        {
          section: 'Gestão',
          items: [
            { path: '/filiais', icon: Store, label: 'Filiais' },
            { path: '/usuarios', icon: Users, label: 'Usuários' },
            { path: '/notificacoes', icon: Bell, label: 'Notificações' },
            { path: '/whatsapp', icon: MessageCircle, label: 'WhatsApp' },
          ]
        },
        {
          section: 'Documentos',
          items: [
            { path: '/documentos', icon: FileText, label: 'Documentos' },
            { path: '/assinaturas', icon: PenTool, label: 'Assinaturas' },
          ]
        },
        {
          section: 'Relatórios',
          items: [
            { path: '/relatorios', icon: TrendingUp, label: 'Relatórios' },
            { path: '/uso', icon: BarChart3, label: 'Uso Mensal' },
          ]
        },
        {
          section: 'Conta',
          items: [
            { path: '/perfil', icon: User, label: 'Meu Perfil' },
          ]
        }
      ];
    }
    
    // Assinante
    return [
      ...commonItems,
      {
        section: 'Documentos',
        items: [
          { path: '/documentos', icon: FileText, label: 'Meus Documentos' },
          { path: '/assinaturas', icon: PenTool, label: 'Assinaturas' },
        ]
      },
      {
        section: 'Conta',
        items: [
          { path: '/perfil', icon: User, label: 'Meu Perfil' },
        ]
      }
    ];
  };
  
  const menuItems = getMenuItems();
  
  return (
    <SidebarContainer 
      collapsed={collapsed} 
      mobileOpen={mobileOpen}
      onClick={(e) => e.stopPropagation()}
    >
      <SidebarHeader>
        <Logo>D</Logo>
        <LogoText collapsed={collapsed}>{appInfo?.app_name || 'DocGest'}</LogoText>
      </SidebarHeader>
      
      <Navigation>
        {menuItems.map((section, sectionIndex) => (
          <NavSection key={sectionIndex}>
            <NavSectionTitle collapsed={collapsed}>
              {section.section}
            </NavSectionTitle>
            {section.items.map((item) => {
              const IconComponent = item.icon;
              return (
                <NavItem
                  key={item.path}
                  to={item.path}
                  active={location.pathname === item.path}
                  onClick={onClose}
                >
                  <NavIcon>
                    <IconComponent size={20} />
                  </NavIcon>
                  <NavText collapsed={collapsed}>{item.label}</NavText>
                </NavItem>
              );
            })}
          </NavSection>
        ))}
      </Navigation>
      
      {user?.empresa && (
        <SidebarFooter>
          <CompanyInfo collapsed={collapsed}>
            <CompanyName>{user.empresa.nome}</CompanyName>
            <CompanyPlan>Plano: {user.empresa.plano?.nome || 'Básico'}</CompanyPlan>
          </CompanyInfo>
        </SidebarFooter>
      )}
    </SidebarContainer>
  );
}

export default Sidebar;