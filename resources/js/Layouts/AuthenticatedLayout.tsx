import ApplicationLogo from '@/components/ApplicationLogo';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Menu } from 'lucide-react';
import { PropsWithChildren, ReactNode } from 'react';

function NavItem({
    href,
    active,
    children,
}: PropsWithChildren<{ href: string; active: boolean }>) {
    return (
        <Link
            href={href}
            className={`inline-flex h-16 items-center border-b-2 px-1 text-sm font-medium transition-colors ${
                active
                    ? 'border-primary text-foreground'
                    : 'text-muted-foreground hover:border-border hover:text-foreground border-transparent'
            }`}
        >
            {children}
        </Link>
    );
}

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user;
    const logout = () => router.post(route('logout'));

    return (
        <div className="bg-background min-h-screen">
            <nav className="border-border bg-card border-b">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-10">
                        <Link href="/" className="flex items-center">
                            <ApplicationLogo className="text-foreground block h-9 w-auto fill-current" />
                        </Link>

                        <div className="hidden sm:flex sm:gap-8">
                            <NavItem
                                href={route('dashboard')}
                                active={route().current('dashboard')}
                            >
                                Dashboard
                            </NavItem>
                        </div>
                    </div>

                    <div className="hidden sm:flex sm:items-center">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm">
                                    {user.name}
                                    <ChevronDown className="ml-1 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <Link href={route('profile.edit')}>
                                        Perfil
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onSelect={logout}>
                                    Sair
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div className="flex items-center sm:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-72">
                                <SheetHeader>
                                    <SheetTitle>{user.name}</SheetTitle>
                                    <p className="text-muted-foreground text-sm">
                                        {user.email}
                                    </p>
                                </SheetHeader>
                                <nav className="mt-6 flex flex-col gap-1 px-4">
                                    <Link
                                        href={route('dashboard')}
                                        className="hover:bg-accent rounded-md px-3 py-2 text-sm"
                                    >
                                        Dashboard
                                    </Link>
                                    <Link
                                        href={route('profile.edit')}
                                        className="hover:bg-accent rounded-md px-3 py-2 text-sm"
                                    >
                                        Perfil
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={logout}
                                        className="hover:bg-accent rounded-md px-3 py-2 text-left text-sm"
                                    >
                                        Sair
                                    </button>
                                </nav>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-border bg-card border-b">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
