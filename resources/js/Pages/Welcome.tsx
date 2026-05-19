import { Head, Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Assinador Digital" />
            <main className="bg-background text-foreground flex min-h-screen flex-col items-center justify-center px-6">
                <div className="max-w-xl text-center">
                    <h1 className="text-4xl font-semibold tracking-tight">
                        Assinador Digital
                    </h1>
                    <p className="text-muted-foreground mt-4">
                        Envie documentos PDF e colete assinaturas em ordem por
                        e-mail — simples, auditável e gratuito.
                    </p>
                    <div className="mt-8 flex justify-center gap-3">
                        <Link
                            href={route('login')}
                            className="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium hover:opacity-90"
                        >
                            Entrar
                        </Link>
                        <Link
                            href={route('register')}
                            className="border-border hover:bg-accent rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            Criar conta
                        </Link>
                    </div>
                </div>
            </main>
        </>
    );
}
