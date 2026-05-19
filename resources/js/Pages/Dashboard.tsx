import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-foreground text-xl leading-tight font-semibold">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="border-border bg-card text-card-foreground overflow-hidden rounded-lg border shadow-xs">
                        <div className="p-6">
                            Bem-vindo. Em breve, seus documentos aparecem aqui.
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
