import Link from 'next/link';

export const metadata = { title: 'contact - AFYAMTOTO AI' };

export default function Page() {
  return (
    <>
      <header className='landing-header'><Link className='auth-brand' href='/'>AFYAMTOTO <span>AI</span></Link></header>
      <main className='landing-section'><h1>contact</h1><p>Contenu en cours de developpement.</p><p><Link href='/'>Retour a l'accueil</Link></p></main>
      <footer className='landing-footer'><p>2026 AFYAMTOTO AI. Tous droits reserves.</p></footer>
    </>
  );
}
