import { readdir, readFile } from "node:fs/promises";
import { join } from "node:path";
import { LinearClient } from "@linear/sdk";

// ── Configuration ──────────────────────────────────────────────────

const LINEAR_API_KEY = process.env.LINEAR_API_KEY;
const APP_NAME = process.env.APP_NAME;
const LINEAR_TEAM_ID = process.env.LINEAR_TEAM_ID;
const LINEAR_ASSIGNEE_ID = process.env.LINEAR_ASSIGNEE_ID;
const MEDIUM_PRIORITY = 3;

if (!LINEAR_API_KEY) {
  console.error("Missing LINEAR_API_KEY in .env file");
  process.exit(1);
}

if (!APP_NAME) {
  console.error("Missing APP_NAME in .env file");
  process.exit(1);
}

if (!LINEAR_TEAM_ID) {
  console.error("Missing LINEAR_TEAM_ID in .env file");
  process.exit(1);
}

if (!LINEAR_ASSIGNEE_ID) {
  console.error("Missing LINEAR_ASSIGNEE_ID in .env file");
  process.exit(1);
}

const client = new LinearClient({ apiKey: LINEAR_API_KEY });

// ── Types ──────────────────────────────────────────────────────────

interface PhaseSection {
  name: string;
  content: string;
}

interface ParsedPhase {
  number: string;
  title: string;
  sections: PhaseSection[];
}

// ── Category label mapping ─────────────────────────────────────────

const CATEGORY_KEYWORDS: [string, string][] = [
  ["Enum", "Enum"],
  ["Migration", "Migration"],
  ["Model", "Model"],
  ["Factor", "Factory"],
  ["Seeder", "Seeder"],
  ["Controller", "Controller"],
  ["Request", "Validation"],
  ["Route", "Route"],
  ["Test", "Test"],
  ["Verification", "Verification"],
  ["Policy", "Policy"],
  ["TypeScript", "TypeScript"],
  ["Type", "TypeScript"],
];

function inferCategoryLabel(sectionName: string): string | undefined {
  for (const [keyword, label] of CATEGORY_KEYWORDS) {
    if (sectionName.includes(keyword)) {
      return label;
    }
  }
  return undefined;
}

// ── Parsing ────────────────────────────────────────────────────────

const PHASE_TITLE_REGEX = /^#\s+Phase\s+(\d+):\s*(.+)$/m;
const SECTION_HEADING_REGEX = /^###\s+\d+\.\s+(.+)$/gm;

function parsePhaseFile(
  content: string,
  filename: string
): ParsedPhase | undefined {
  const titleMatch = content.match(PHASE_TITLE_REGEX);
  if (!titleMatch) {
    console.warn(`Could not parse phase title from ${filename}, skipping`);
    return undefined;
  }

  const number = titleMatch[1].padStart(2, "0");
  const title = titleMatch[2].trim();

  const sections: PhaseSection[] = [];
  const matches = [...content.matchAll(SECTION_HEADING_REGEX)];

  for (let i = 0; i < matches.length; i++) {
    const match = matches[i];
    const name = match[1].trim();
    const start = match.index + match[0].length;
    const end = i + 1 < matches.length ? matches[i + 1].index : content.length;
    const sectionContent = content.slice(start, end).trim();
    sections.push({ name, content: sectionContent });
  }

  return { number, title, sections };
}

// ── Label management ───────────────────────────────────────────────

const labelCache = new Map<string, string>();

async function ensureLabel(name: string): Promise<string> {
  const cached = labelCache.get(name);
  if (cached) {
    return cached;
  }

  const result = await client.createIssueLabel({
    name,
    teamId: LINEAR_TEAM_ID,
  });

  const label = await result.issueLabel;
  if (!label) {
    throw new Error(`Failed to create label: ${name}`);
  }

  labelCache.set(name, label.id);
  return label.id;
}

async function loadExistingLabels(): Promise<void> {
  const team = await client.team(LINEAR_TEAM_ID);
  const labels = await team.labels();

  for (const label of labels.nodes) {
    labelCache.set(label.name, label.id);
  }

  console.log(`Loaded ${labels.nodes.length} existing team labels`);
}

// ── Main sync ──────────────────────────────────────────────────────

async function main(): Promise<void> {
  const phaseFilter = process.argv[2];
  console.log(`Syncing phases to Linear for "${APP_NAME}"...\n`);

  await loadExistingLabels();

  const specsDir = join(import.meta.dir, "..", "specs", "plan");
  const files = await readdir(specsDir);
  let phaseFiles = files
    .filter((f) => f.startsWith("phase-") && f.endsWith(".md"))
    .sort();

  if (phaseFilter) {
    const padded = phaseFilter.padStart(2, "0");
    phaseFiles = phaseFiles.filter((f) => f.startsWith(`phase-${padded}`));
    console.log(`Filtering to phase ${padded}\n`);
  }

  console.log(`Found ${phaseFiles.length} phase files\n`);

  // Create a single project for the app
  const projectResult = await client.createProject({
    name: APP_NAME,
    teamIds: [LINEAR_TEAM_ID],
    leadId: LINEAR_ASSIGNEE_ID,
    priority: MEDIUM_PRIORITY,
  });

  const project = await projectResult.project;
  if (!project) {
    throw new Error(`Failed to create project: ${APP_NAME}`);
  }

  console.log(`Created project: ${project.name}\n`);

  for (const file of phaseFiles) {
    const content = await readFile(join(specsDir, file), "utf-8");
    const phase = parsePhaseFile(content, file);
    if (!phase) {
      continue;
    }

    console.log(`── Phase ${phase.number}: ${phase.title} ──`);

    // Create phase label
    const phaseLabelId = await ensureLabel(`Phase ${phase.number}`);

    // Create milestone for this phase
    const milestoneResult = await client.createProjectMilestone({
      projectId: project.id,
      name: `Phase ${phase.number}: ${phase.title}`,
    });

    const milestone = await milestoneResult.projectMilestone;
    if (!milestone) {
      throw new Error(`Failed to create milestone for Phase ${phase.number}`);
    }

    console.log(`  Created milestone: ${milestone.name}`);

    // Create issues for each section
    for (const section of phase.sections) {
      const labelIds = [phaseLabelId];

      const category = inferCategoryLabel(section.name);
      if (category) {
        const categoryLabelId = await ensureLabel(category);
        labelIds.push(categoryLabelId);
      }

      const title = `Phase ${phase.number}: Create ${section.name}`;

      const issueResult = await client.createIssue({
        teamId: LINEAR_TEAM_ID,
        title,
        description: section.content,
        assigneeId: LINEAR_ASSIGNEE_ID,
        priority: MEDIUM_PRIORITY,
        labelIds,
        projectId: project.id,
        projectMilestoneId: milestone.id,
      });

      const issue = await issueResult.issue;
      if (!issue) {
        throw new Error(`Failed to create issue: ${title}`);
      }

      const categoryTag = category ? ` [${category}]` : "";
      console.log(`    Issue: ${issue.identifier} — ${title}${categoryTag}`);
    }

    console.log();
  }

  console.log("Sync complete!");
}

main().catch((error) => {
  console.error("Sync failed:", error);
  process.exit(1);
});
