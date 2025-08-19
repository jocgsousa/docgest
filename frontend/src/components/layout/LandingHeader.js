import React, { useState } from 'react';
import styled from 'styled-components';
import { Link, useNavigate } from 'react-router-dom';
import Button from '../Button';
import { Menu, X } from 'lucide-react';

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
  z-index: 50;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
`;

const LeftSection = styled.div`
  display: flex;
  align-items: center;
  gap: 2rem;
`;

const LogoContainer = styled(Link)`
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: inherit;
`;

const Logo = styled.div`
  width: 2.5rem;
  height: 2.5rem;
  background-color: ${props => props.theme.colors.primary};
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.white};
  font-weight: 700;
  font-size: 1.25rem;
`;

const LogoText = styled.span`
  font-size: 1.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
`;

const Navigation = styled.nav`
  display: flex;
  align-items: center;
  gap: 2rem;
  
  @media (max-width: 768px) {
    display: none;
  }
`;

const NavLink = styled(Link)`
  color: ${props => props.theme.colors.gray[600]};
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s ease-in-out;
  
  &:hover {
    color: ${props => props.theme.colors.gray[900]};
  }
`;

const RightSection = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
  
  @media (max-width: 768px) {
    display: none;
  }
`;

const MobileMenuButton = styled.button`
  display: none;
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
  
  @media (max-width: 768px) {
    display: flex;
    align-items: center;
    justify-content: center;
  }
`;

const MobileMenu = styled.div`
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: ${props => props.theme.colors.white};
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  padding: 1rem 1.5rem;
  
  @media (max-width: 768px) {
    display: ${props => props.$isOpen ? 'block' : 'none'};
  }
`;

const MobileNavLink = styled(Link)`
  display: block;
  color: ${props => props.theme.colors.gray[600]};
  font-weight: 500;
  text-decoration: none;
  padding: 0.75rem 0;
  border-bottom: 1px solid ${props => props.theme.colors.gray[100]};
  transition: color 0.2s ease-in-out;
  
  &:hover {
    color: ${props => props.theme.colors.gray[900]};
  }
  
  &:last-child {
    border-bottom: none;
  }
`;

const MobileButtonContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
`;

function LandingHeader() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const navigate = useNavigate();

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  const handleTestClick = () => {
    navigate('/cadastro?tipo=empresa&teste=true');
  };

  const handleLoginClick = () => {
    navigate('/login');
  };

  return (
    <HeaderContainer>
      <LeftSection>
        <LogoContainer to="/">
          <Logo>D</Logo>
          <LogoText>DocGest</LogoText>
        </LogoContainer>
        
        <Navigation>
          <NavLink to="/sobre">Sobre</NavLink>
          <NavLink to="/planos-public">Planos</NavLink>
          <NavLink to="/contato">Contato</NavLink>
        </Navigation>
      </LeftSection>
      
      <RightSection>
        <Button 
          $variant="outline" 
          size="sm"
          onClick={handleTestClick}
        >
          Realizar Teste
        </Button>
        <Button 
          $variant="primary" 
          size="sm"
          onClick={handleLoginClick}
        >
          Login
        </Button>
      </RightSection>
      
      <MobileMenuButton onClick={toggleMobileMenu}>
        {mobileMenuOpen ? <X size={20} /> : <Menu size={20} />}
      </MobileMenuButton>
      
      <MobileMenu $isOpen={mobileMenuOpen}>
        <MobileNavLink to="/sobre" onClick={() => setMobileMenuOpen(false)}>
          Sobre
        </MobileNavLink>
        <MobileNavLink to="/planos-public" onClick={() => setMobileMenuOpen(false)}>
          Planos
        </MobileNavLink>
        <MobileNavLink to="/contato" onClick={() => setMobileMenuOpen(false)}>
          Contato
        </MobileNavLink>
        
        <MobileButtonContainer>
          <Button 
            $variant="outline" 
            size="sm"
            $fullWidth
            onClick={() => {
              setMobileMenuOpen(false);
              handleTestClick();
            }}
          >
            Realizar Teste
          </Button>
          <Button 
            $variant="primary" 
            size="sm"
            $fullWidth
            onClick={() => {
              setMobileMenuOpen(false);
              handleLoginClick();
            }}
          >
            Login
          </Button>
        </MobileButtonContainer>
      </MobileMenu>
    </HeaderContainer>
  );
}

export default LandingHeader;