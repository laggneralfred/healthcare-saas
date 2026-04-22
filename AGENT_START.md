# AGENT_START.md

## Purpose

This file is the entry point for any AI agent (Codex, Claude, or others) working on this Laravel healthcare application.

You MUST read the referenced documentation before making changes.

---

## Step 1 — Read Core Project Context

Start with these files:

1. README.md
2. CLAUDE.md
3. IMPLEMENTATION_COMPLETE.md
4. DEMO.md

Goal:

* Understand what the application does
* Understand current feature set
* Understand prior decisions and constraints

---

## Step 2 — Understand Deployment & Environment

Read:

* DEPLOYMENT.md
* DOCKER_DEPLOYMENT.md
* DOCKER_README.md

Goal:

* Understand how the app is run locally and in production
* Understand Docker setup and constraints
* Do NOT introduce changes that break deployment

---

## Step 3 — Import System (if task is related)

If working with patient data or CSV import, also read:

* CSV_IMPORT_GUIDE.md
* PATIENT_IMPORT_README.md
* IMPORTER_INDEX.md
* IMPORTER_FILES_REFERENCE.md

Goal:

* Understand importer architecture
* Avoid breaking existing import flows
* Extend rather than rewrite

---

## Step 4 — Working Rules (MANDATORY)

* Do NOT rewrite working code unless explicitly instructed
* Prefer minimal, surgical changes (small diffs)
* Preserve existing Laravel conventions and structure
* Do NOT introduce new frameworks or major refactors
* Reuse existing controllers/services before creating new ones
* Keep naming consistent with existing codebase
* If unsure, ASK before changing architecture

---

## Step 5 — Change Workflow

Before making any changes:

1. Summarize your understanding of:

   * the feature involved
   * the files likely affected

2. Propose a plan:

   * list exact files to change
   * explain why each change is needed

3. Wait for confirmation if the change is non-trivial

After making changes:

* List all modified files
* Explain what was changed and why
* Keep changes easy to review

---

## Step 6 — Safety Constraints

* Do NOT break authentication flow
* Do NOT change database schema unless required by task
* Do NOT remove existing routes or endpoints without confirmation
* Do NOT modify deployment configs unless explicitly required

---

## Step 7 — Testing & Verification

After changes:

* Verify affected routes/controllers still function
* Ensure no obvious Laravel errors (routes, bindings, views)
* If possible, describe how to manually test the change

---

## Step 8 — Mindset

This is an **incrementally built system**, not a greenfield project.

Your role is to:

* extend
* fix
* stabilize

NOT to:

* redesign
* optimize prematurely
* refactor broadly

---

## Optional Instruction

If starting fresh in a session, begin with:

"Read AGENT_START.md and all referenced files.
Summarize the project and propose the next safe step.
Do not change code yet."

---
