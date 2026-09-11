export const gitCommandAdditions = [
  {
    id: 'config',
    command: 'git config',
    category: 'Setup & configuration',
    level: 'Basic',
    risk: 'caution',
    purpose: 'Inspect and configure Git behavior and identity.',
    explanation: 'Reads or writes Git configuration at system, global, local, or worktree scope. Repository-local settings can override global defaults, so inspect the active value and its origin when behavior is surprising.',
    when: 'Setting your Git identity, inspecting configuration, or defining repository-specific behavior such as pull strategy.',
    avoid: 'Do not copy configuration commands blindly. Understand the scope first, especially when changing global settings used by every repository.',
    syntax: 'git config [--global|--local] <key> [value]',
    examples: [
      'git config --list --show-origin',
      'git config --global user.name "Your Name"',
      'git config --global user.email "you@example.com"',
      'git config --local pull.ff only'
    ],
    flags: [
      '--list: list configuration',
      '--show-origin: show which file supplied each value',
      '--global: use the user-level configuration',
      '--local: use the current repository configuration'
    ],
    impact: { working: 'none', index: 'none', local: 'write', remote: 'none' },
    mistakes: 'Changing a global setting when only one repository should be affected, or committing an identity/configuration assumption without checking its origin.',
    related: ['init', 'remote', 'pull']
  }
];
