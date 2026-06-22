import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DocumentData, PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { SyntheticEvent } from 'react';

type EditProps = PageProps<{
    document: DocumentData;
}>;

export default function Edit({ document }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: document.title,
        description: document.description ?? '',
    });

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        put(route('documents.update', document.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-foreground text-xl leading-tight font-semibold">
                    Editar documento
                </h2>
            }
        >
            <Head title={`Editar — ${document.title}`} />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <Button asChild variant="ghost" size="sm" className="mb-4">
                        <Link href={route('documents.show', document.id)}>
                            <ArrowLeft className="h-4 w-4" />
                            Voltar
                        </Link>
                    </Button>

                    <div className="border-border bg-card text-card-foreground rounded-lg border p-4 shadow-sm sm:p-8">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Título</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    autoFocus
                                    required
                                />
                                {errors.title && (
                                    <p className="text-destructive text-sm">
                                        {errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">
                                    Descrição{' '}
                                    <span className="text-muted-foreground">
                                        (opcional)
                                    </span>
                                </Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                />
                                {errors.description && (
                                    <p className="text-destructive text-sm">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Salvando...' : 'Salvar'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
