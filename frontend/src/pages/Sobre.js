import React from 'react';
import styled from 'styled-components';
import { useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import LandingHeader from '../components/layout/LandingHeader';
import { Shield, Users, Award, Target, Heart, Zap } from 'lucide-react';

const Container = styled.div`
  min-height: 100vh;
  background-color: ${props => props.theme.colors.white};
`;

const HeroSection = styled.section`
  padding: 4rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, ${props => props.theme.colors.gray[50]} 0%, ${props => props.theme.colors.white} 100%);
`;

const HeroContent = styled.div`
  max-width: 4xl;
  margin: 0 auto;
`;

const HeroTitle = styled.h1`
  font-size: 3rem;
  font-weight: 800;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1.5rem;
  line-height: 1.1;
  
  @media (max-width: 768px) {
    font-size: 2.25rem;
  }
`;

const HeroSubtitle = styled.p`
  font-size: 1.25rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2.5rem;
  max-width: 3xl;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6;
`;

const SectionContainer = styled.div`
  max-width: 6xl;
  margin: 0 auto;
`;

const Section = styled.section`
  padding: 4rem 1.5rem;
`;

const SectionTitle = styled.h2`
  font-size: 2.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  text-align: center;
  margin-bottom: 1rem;
  
  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const SectionSubtitle = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[600]};
  text-align: center;
  margin-bottom: 3rem;
  max-width: 3xl;
  margin-left: auto;
  margin-right: auto;
`;

const ValuesGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 4rem;
`;

const ValueCard = styled.div`
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.75rem;
  padding: 2rem;
  text-align: center;
  transition: all 0.3s ease-in-out;
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }
`;

const ValueIcon = styled.div`
  width: 4rem;
  height: 4rem;
  background-color: ${props => props.theme.colors.primary + '20'};
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: ${props => props.theme.colors.primary};
`;

const ValueTitle = styled.h3`
  font-size: 1.25rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1rem;
`;

const ValueDescription = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  line-height: 1.6;
`;

const StorySection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[50]};
`;

const StoryContent = styled.div`
  max-width: 4xl;
  margin: 0 auto;
  text-align: center;
`;

const StoryText = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[700]};
  line-height: 1.8;
  margin-bottom: 2rem;
`;

const StatsSection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.white};
`;

const StatsGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  max-width: 4xl;
  margin: 0 auto;
`;

const StatCard = styled.div`
  text-align: center;
  padding: 1.5rem;
`;

const StatNumber = styled.div`
  font-size: 3rem;
  font-weight: 800;
  color: ${props => props.theme.colors.primary};
  margin-bottom: 0.5rem;
`;

const StatLabel = styled.div`
  font-size: 1rem;
  color: ${props => props.theme.colors.gray[600]};
  font-weight: 500;
`;

const CTASection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  text-align: center;
`;

const CTATitle = styled.h2`
  font-size: 2.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.white};
  margin-bottom: 1rem;
  
  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const CTASubtitle = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[300]};
  margin-bottom: 2.5rem;
  max-width: 2xl;
  margin-left: auto;
  margin-right: auto;
`;

const CTAButtons = styled.div`
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
`;

const Footer = styled.footer`
  padding: 2rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  border-top: 1px solid ${props => props.theme.colors.gray[800]};
  text-align: center;
`;

const FooterText = styled.p`
  color: ${props => props.theme.colors.gray[400]};
  font-size: 0.875rem;
`;

function Sobre() {
  const navigate = useNavigate();

  const handleTestClick = () => {
    navigate('/cadastro?tipo=empresa&teste=true');
  };

  const handleContactClick = () => {
    navigate('/contato');
  };

  const values = [
    {
      icon: <Shield size={24} />,
      title: 'Segurança',
      description: 'Protegemos seus documentos com os mais altos padrões de segurança e criptografia avançada.'
    },
    {
      icon: <Users size={24} />,
      title: 'Colaboração',
      description: 'Facilitamos o trabalho em equipe com ferramentas intuitivas de colaboração e compartilhamento.'
    },
    {
      icon: <Award size={24} />,
      title: 'Excelência',
      description: 'Buscamos constantemente a excelência em nossos produtos e no atendimento aos nossos clientes.'
    },
    {
      icon: <Target size={24} />,
      title: 'Foco no Cliente',
      description: 'Desenvolvemos soluções pensando nas necessidades reais dos nossos usuários e empresas.'
    },
    {
      icon: <Heart size={24} />,
      title: 'Compromisso',
      description: 'Estamos comprometidos em transformar a gestão de documentos de forma sustentável e eficiente.'
    },
    {
      icon: <Zap size={24} />,
      title: 'Inovação',
      description: 'Utilizamos as mais modernas tecnologias para oferecer soluções inovadoras e eficazes.'
    }
  ];

  const stats = [
    { number: '10K+', label: 'Documentos Processados' },
    { number: '500+', label: 'Empresas Atendidas' },
    { number: '99.9%', label: 'Uptime Garantido' },
    { number: '24/7', label: 'Suporte Disponível' }
  ];

  return (
    <Container>
      <LandingHeader />
      
      <HeroSection>
        <HeroContent>
          <HeroTitle>
            Sobre o DocGest
          </HeroTitle>
          <HeroSubtitle>
            Somos uma empresa dedicada a revolucionar a gestão de documentos, 
            oferecendo soluções digitais seguras, eficientes e sustentáveis para empresas de todos os tamanhos.
          </HeroSubtitle>
        </HeroContent>
      </HeroSection>

      <StorySection>
        <SectionContainer>
          <StoryContent>
            <SectionTitle>Nossa História</SectionTitle>
            <StoryText>
              O DocGest nasceu da necessidade de simplificar e modernizar os processos de gestão documental. 
              Fundada por uma equipe de especialistas em tecnologia e gestão empresarial, nossa missão é 
              eliminar a burocracia desnecessária e acelerar os processos de negócio através da digitalização inteligente.
            </StoryText>
            <StoryText>
              Desde o início, focamos em desenvolver uma plataforma que não apenas digitalize documentos, 
              mas que transforme completamente a forma como as empresas lidam com seus processos documentais, 
              garantindo segurança jurídica, eficiência operacional e sustentabilidade ambiental.
            </StoryText>
          </StoryContent>
        </SectionContainer>
      </StorySection>

      <Section>
        <SectionContainer>
          <SectionTitle>Nossos Valores</SectionTitle>
          <SectionSubtitle>
            Os princípios que guiam nosso trabalho e definem nossa cultura empresarial
          </SectionSubtitle>
          
          <ValuesGrid>
            {values.map((value, index) => (
              <ValueCard key={index}>
                <ValueIcon>
                  {value.icon}
                </ValueIcon>
                <ValueTitle>{value.title}</ValueTitle>
                <ValueDescription>{value.description}</ValueDescription>
              </ValueCard>
            ))}
          </ValuesGrid>
        </SectionContainer>
      </Section>

      <StatsSection>
        <SectionContainer>
          <SectionTitle>Nossos Números</SectionTitle>
          <SectionSubtitle>
            Resultados que demonstram nossa dedicação e o sucesso dos nossos clientes
          </SectionSubtitle>
          
          <StatsGrid>
            {stats.map((stat, index) => (
              <StatCard key={index}>
                <StatNumber>{stat.number}</StatNumber>
                <StatLabel>{stat.label}</StatLabel>
              </StatCard>
            ))}
          </StatsGrid>
        </SectionContainer>
      </StatsSection>

      <CTASection>
        <SectionContainer>
          <CTATitle>Pronto para conhecer o DocGest?</CTATitle>
          <CTASubtitle>
            Junte-se às centenas de empresas que já transformaram sua gestão de documentos. 
            Comece seu teste gratuito hoje mesmo!
          </CTASubtitle>
          <CTAButtons>
            <Button 
              $variant="white" 
              size="lg"
              onClick={handleTestClick}
            >
              Começar Teste Gratuito
            </Button>
            <Button 
              $variant="outline-white" 
              size="lg"
              onClick={handleContactClick}
            >
              Falar com Especialista
            </Button>
          </CTAButtons>
        </SectionContainer>
      </CTASection>

      <Footer>
        <FooterText>
          © 2024 DocGest. Todos os direitos reservados.
        </FooterText>
      </Footer>
    </Container>
  );
}

export default Sobre;