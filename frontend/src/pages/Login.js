import React, { useState } from 'react';
import styled from 'styled-components';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useApp } from '../contexts/AppContext';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';

const LoginContainer = styled.div`
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, ${props => props.theme.colors.primary}10 0%, ${props => props.theme.colors.primary}05 100%);
  padding: 1rem;
`;

const LoginCard = styled(Card)`
  width: 100%;
  max-width: 24rem;
  padding: 2rem;
`;

const LogoContainer = styled.div`
  text-align: center;
  margin-bottom: 2rem;
`;

const Logo = styled.div`
  width: 4rem;
  height: 4rem;
  background-color: ${props => props.theme.colors.primary};
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.white};
  font-weight: 700;
  font-size: 1.5rem;
  margin: 0 auto 1rem;
`;

const Title = styled.h1`
  font-size: 1.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0 0 0.5rem 0;
  text-align: center;
`;

const Subtitle = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  margin: 0 0 2rem 0;
  text-align: center;
  font-size: 0.875rem;
`;

const Form = styled.form`
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
`;

const ErrorMessage = styled.div`
  background-color: ${props => props.theme.colors.danger}10;
  border: 1px solid ${props => props.theme.colors.danger}30;
  color: ${props => props.theme.colors.danger};
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  text-align: center;
`;

const ForgotPassword = styled.button`
  background: none;
  border: none;
  color: ${props => props.theme.colors.primary};
  font-size: 0.875rem;
  cursor: pointer;
  text-align: center;
  padding: 0.5rem 0;
  transition: color 0.2s ease-in-out;
  
  &:hover {
    color: ${props => props.theme.colors.primaryHover};
    text-decoration: underline;
  }
`;

const SignupLink = styled.div`
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  
  p {
    color: ${props => props.theme.colors.gray[600]};
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }
  
  a {
    color: ${props => props.theme.colors.primary};
    text-decoration: none;
    font-weight: 500;
    
    &:hover {
      text-decoration: underline;
    }
  }
`;

const Footer = styled.div`
  margin-top: 2rem;
  text-align: center;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[500]};
`;

function Login() {
  const { user, login } = useAuth();
  const { appInfo } = useApp();
  const [formData, setFormData] = useState({
    email: '',
    senha: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  
  // Redirecionar se já estiver logado
  if (user) {
    return <Navigate to="/dashboard" replace />;
  }
  
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Limpar erro quando o usuário começar a digitar
    if (error) {
      setError('');
    }
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.email || !formData.senha) {
      setError('Por favor, preencha todos os campos.');
      return;
    }
    
    setLoading(true);
    setError('');
    
    const result = await login(formData.email, formData.senha);
    
    if (!result.success) {
      setError(result.error || 'Erro ao fazer login. Verifique suas credenciais.');
    }
    
    setLoading(false);
  };
  
  const togglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };
  
  return (
    <LoginContainer>
      <LoginCard>
        <LogoContainer>
          <Logo>{appInfo.app_name?.charAt(0) || 'D'}</Logo>
          <Title>{appInfo.app_name}</Title>
          <Subtitle>Faça login para acessar sua conta</Subtitle>
        </LogoContainer>
        
        <Form onSubmit={handleSubmit}>
          {error && (
            <ErrorMessage>
              {error}
            </ErrorMessage>
          )}
          
          <Input
            type="email"
            name="email"
            label="E-mail"
            placeholder="seu@email.com"
            value={formData.email}
            onChange={handleChange}
            disabled={loading}
            icon="📧"
            required
          />
          
          <Input
            type={showPassword ? 'text' : 'password'}
            name="senha"
            label="Senha"
            placeholder="Digite sua senha"
            value={formData.senha}
            onChange={handleChange}
            disabled={loading}
            icon="🔒"
            rightIcon={showPassword ? '👁️' : '👁️‍🗨️'}
            onRightIconClick={togglePasswordVisibility}
            required
          />
          
          <Button
            type="submit"
            $variant="primary"
            size="lg"
            $fullWidth
            disabled={loading}
          >
            {loading ? 'Entrando...' : 'Entrar'}
          </Button>
          
          <ForgotPassword type="button">
            Esqueceu sua senha?
          </ForgotPassword>
        </Form>
        
        <SignupLink>
          <p>Não tem uma conta?</p>
          <a href="/cadastro">Criar conta</a>
        </SignupLink>
        
        <Footer>
          © 2024 {appInfo?.app_name || 'DocGest'}. Todos os direitos reservados.
        </Footer>
      </LoginCard>
    </LoginContainer>
  );
}

export default Login;