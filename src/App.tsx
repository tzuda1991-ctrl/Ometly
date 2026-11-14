import { CheckCircle2, Database, FolderGit2, Server, Smartphone } from 'lucide-react';

const SECTIONS = [
  {
    icon: Server,
    title: 'Railway Backend (backend/)',
    items: [
      'Dockerfile (php:8.0-apache + storage alias)',
      'docker-entrypoint.sh (auto storage:link & dynamic PORT)',
      'railway.json configured',
      'CheckLogin.php auth bypass patched',
      'LoginController.php plaintext/encrypted login crash patched',
    ],
  },
  {
    icon: Smartphone,
    title: 'GitHub Actions APK (.github/workflows/build-apk.yml)',
    items: [
      'Java 21 + Flutter stable configured for Gradle 9.3.1 / AGP 8.13.2',
      'build.gradle updated for optional release keystore signing',
      'Old developer orange.jks and .cxx build cache removed',
    ],
  },
  {
    icon: Database,
    title: 'Database (database/orange_database.sql)',
    items: [
      '27 MySQL tables ready to import into Railway MySQL',
      'Update ConstRes.base in mobile/lib/utils/const_res.dart after Railway deploy',
    ],
  },
];

export default function App() {
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6 md:p-12">
      <div className="max-w-4xl mx-auto space-y-8">
        <header className="space-y-3 border-b border-slate-800 pb-6">
          <div className="flex items-center gap-3 text-orange-400">
            <FolderGit2 className="w-7 h-7" />
            <span className="text-sm font-semibold tracking-wider uppercase">
              Repository Prepared
            </span>
          </div>
          <h1 className="text-3xl font-bold tracking-tight">
            Orange Dating &amp; Livestream — Git, Railway &amp; APK CI
          </h1>
          <p className="text-slate-400">
            Clean project files are staged in this workspace. Commit and push via the AI Studio
            GitHub panel to deploy on Railway and trigger the GitHub Actions APK build.
          </p>
        </header>

        <div className="grid gap-6 md:grid-cols-3">
          {SECTIONS.map((section) => {
            const Icon = section.icon;
            return (
              <div
                key={section.title}
                className="rounded-xl border border-slate-800 bg-slate-900/60 p-5 space-y-4"
              >
                <div className="flex items-center gap-2.5 text-orange-400">
                  <Icon className="w-5 h-5 shrink-0" />
                  <h2 className="font-semibold text-slate-100 text-sm">{section.title}</h2>
                </div>
                <ul className="space-y-2.5 text-xs text-slate-300">
                  {section.items.map((item) => (
                    <li key={item} className="flex items-start gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                      <span>{item}</span>
                    </li>
                  ))}
                </ul>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
