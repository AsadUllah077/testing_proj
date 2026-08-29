# HRM Module — AI Integration Plan

**Project:** ERP V4  
**Module:** Human Resource Management (HRM)  
**Date:** 2026-04-15  
**Author:** Review by Claude Code (Sonnet 4.6)  
**Branch:** hilal_dev  

---

## Executive Summary

After a full review of the HRM module (130+ files across 10 web controllers, 5 API controllers, 50 Blade templates, 13 models, and 42 migrations), there are **7 high-value AI integration points** across the module's sub-systems. The existing architecture — multi-tenant Laravel MVC with a clean API layer, Sanctum auth, and rich relational data — is well-suited to receive AI features without requiring architectural rewrites.

---

## Module Sub-Systems Reviewed

| Sub-Module | Files Reviewed | AI Opportunity |
|---|---|---|
| Recruitment & Candidates | `CandidateController.php`, `JobController.php`, 12 views | HIGH |
| Employee Management | `EmployeeController.php`, 5 views, 13 models | HIGH |
| Attendance | `AttendanceController.php`, API controller, 6 views | HIGH |
| Leave Management | `LeaveController.php`, API controller, 8 views | MEDIUM |
| Payroll (integrated) | `PayrollService.php` (26K+ lines) | MEDIUM |
| Dashboard & Analytics | `DashboardController.php` (1,058 lines) | HIGH |
| Announcements | `AnnouncementController.php`, 6 views | LOW |

---

## AI Integration Points

---

### 1. Resume Parsing & Candidate Scoring (Recruitment)

**Where:** `CandidateController.php` — `store()` and `show()` methods  
**Views:** `candidates/create.blade.php`, `candidates/show.blade.php`

**Current State:**  
Candidates are added manually. Resume files are uploaded and stored via `Storage::disk()`. There is no automated extraction of skills, experience, or education from resumes. Candidate rating is entered manually.

**Proposed AI Feature:**

When a resume is uploaded, call a Claude API endpoint to:
1. Extract structured data: skills, years of experience, education, previous roles.
2. Score the candidate (0–100) against the job's required skills (from `config/hrm.php` — 45 predefined skills already defined).
3. Auto-populate the candidate's skills field and generate a short summary paragraph.

**Implementation Sketch:**

```php
// In CandidateController::store()
// After file is saved:
$resumeText = $this->extractTextFromPdf($resumePath);
$aiScore = AiRecruitmentService::scoreCandidate($resumeText, $job->required_skills);

$candidate->ai_score       = $aiScore['score'];         // 0-100
$candidate->ai_summary     = $aiScore['summary'];       // 2-3 sentence summary
$candidate->extracted_skills = $aiScore['skills'];     // JSON array
$candidate->save();
```

**New service:** `app/Services/HRM/AiRecruitmentService.php`

**Database change:** Add `ai_score`, `ai_summary`, `extracted_skills` columns to `hrm_candidates` table.

**API:** Claude `claude-sonnet-4-6` with structured output (JSON mode).

**Business Value:**  
- Removes manual screening time for HR staff.
- Provides consistent, bias-reduced ranking.
- Surfaces qualified candidates that may have been overlooked.

---

### 2. Interview Question Generator (Recruitment)

**Where:** `CandidateController.php` — `updateInterviewDetails()` method  
**Views:** `candidates/show.blade.php`

**Current State:**  
Interview scheduling captures date/time and interviewer. There is no support for generating structured interview questions based on the role or candidate background.

**Proposed AI Feature:**

Add a button on the candidate detail page — **"Generate Interview Questions"** — that calls the backend with the job title, required skills, and the candidate's extracted skills/experience. Claude returns a structured set of questions in categories:

- Technical (role-specific)
- Behavioral (STAR-format)
- Culture / soft skills
- Gap-probing (based on missing skills vs. job requirements)

**Implementation Sketch:**

```php
// New route: POST /hrm/candidates/{candidate}/interview-questions
public function generateInterviewQuestions(HrmCandidate $candidate)
{
    $questions = AiRecruitmentService::generateQuestions(
        jobTitle: $candidate->job->job_title,
        requiredSkills: $candidate->job->skills,
        candidateSkills: $candidate->extracted_skills,
        experienceYears: $candidate->experience_years
    );

    return response()->json(['questions' => $questions]);
}
```

**Business Value:**  
- Saves 20–30 minutes of interview prep per candidate.
- Ensures structured and consistent interviewing across managers.

---

### 3. Attrition / Turnover Risk Prediction (Employee Management)

**Where:** `EmployeeController.php` — `show()`, `index()` methods  
**Views:** `employee/show.blade.php`, `employee/index.blade.php`  
**Dashboard:** `DashboardController.php`

**Current State:**  
Employee status transitions (active → resigned/terminated) are tracked but there is no predictive mechanism. HR only discovers attrition risk after the employee has resigned.

**Proposed AI Feature:**

A periodic job (weekly) analyses each active employee's behavioral signals and outputs a risk score:

| Signal | Data Source |
|---|---|
| Attendance rate decline | `employee_attendances` |
| Increase in leave requests | `leave_records` |
| Late arrivals trend | `employee_attendances.late` |
| Tenure vs. average tenure | `employees.joining_date` |
| Days since last promotion/pay raise | `employee_pay_structures` |

The score (Low / Medium / High) is stored per employee and surfaced on the dashboard and employee profile.

**Implementation Sketch:**

```php
// New: app/Jobs/HRM/CalculateAttritionRiskJob.php (runs weekly via scheduler)
// New: app/Services/HRM/AiAttritionService.php

// Prompt to Claude: summarize signals, return JSON:
// { "risk_level": "high", "risk_score": 78, "top_reasons": [...], "suggested_actions": [...] }
```

**Database change:** Add `attrition_risk_score`, `attrition_risk_level`, `attrition_risk_updated_at` to `employees` table.

**Business Value:**  
- Enables proactive retention conversations.
- Reduces cost of replacement hiring (typically 50–200% of annual salary).

---

### 4. Attendance Anomaly Detection (Attendance Management)

**Where:** `AttendanceController.php` — `index()` and `show()`  
**API:** `Api/HRM/AttendanceController.php`  
**Views:** `attendance/index.blade.php`

**Current State:**  
Attendance is recorded and displayed with statuses (Present, Absent, Late, Remote). There is manual review of attendance data. Overtime is calculated but not pattern-analyzed.

**Proposed AI Feature:**

A background analysis job that runs nightly on attendance data to detect:

1. **Buddy punching patterns** — Two employees consistently checking in/out within seconds of each other (API check-in timestamps).
2. **Ghost attendance** — Check-in with no productive hours logged vs. project/task system.
3. **Unusual overtime clusters** — Sudden overtime spikes in a department that don't match project load.
4. **Chronic lateness patterns** — Employees trending toward lateness (grace period is 15 min).

Anomalies are flagged in the attendance dashboard with a severity tag and natural language explanation.

**Implementation Sketch:**

```php
// New: app/Services/HRM/AiAttendanceAnalyticsService.php

$anomalies = AiAttendanceAnalyticsService::detectAnomalies(
    companyId: $companyId,
    dateRange: [now()->subDays(30), now()]
);

// Each anomaly: { type, employee_id, severity, explanation, recommended_action }
```

**Business Value:**  
- Reduces time theft and attendance fraud.
- Surfaces systemic issues (department under-staffing causing overtime) before they escalate.

---

### 5. Smart Leave Recommendation & Anomaly Flagging (Leave Management)

**Where:** `LeaveController.php` — `store()`, `updateStatus()` methods  
**Views:** `leave/index.blade.php`, `leave/show.blade.php`, `leave/pending.blade.php`

**Current State:**  
Leave approval is manual. Leave balance is calculated automatically but there is no AI-assisted approval guidance or pattern detection. The `pendingLeaveRequests()` method returns pending requests for manual review.

**Proposed AI Feature A — Approval Recommendation:**

When a leave request is submitted, before the manager sees it, AI evaluates:
- Remaining leave balance vs. request duration.
- Team coverage on requested dates (are other team members already on leave?).
- Proximity to deadlines or critical project milestones (cross-reference Task module).
- Employee's historical leave pattern (frequent Mondays/Fridays — possible long-weekend pattern).

Returns: `recommended_action` (Approve / Flag for review / Decline) + a reason visible to the manager.

**Proposed AI Feature B — Leave Pattern Abuse Detection:**

Monthly analysis per employee flags patterns like:
- Consistently taking leave on specific weekdays.
- Leave requests immediately after salary credit day.
- Repeated single-day leaves without medical documentation.

**Implementation Sketch:**

```php
// In LeaveController::store() — after validation, before notification:
$recommendation = AiLeaveService::recommendApproval($leaveRecord, $employee);
$leaveRecord->ai_recommendation       = $recommendation['action'];
$leaveRecord->ai_recommendation_reason = $recommendation['reason'];
$leaveRecord->save();
```

**Database change:** Add `ai_recommendation`, `ai_recommendation_reason` to `leave_records` table.

**Business Value:**  
- Reduces manager decision fatigue for routine approvals.
- Creates an audit trail with AI-assisted reasoning.

---

### 6. AI HR Assistant Chatbot (Dashboard / Global)

**Where:** `DashboardController.php` and as a global sidebar widget  
**Views:** `hrm/dashboard.blade.php`

**Current State:**  
The dashboard displays static aggregate metrics (attendance rate, leave counts, recruitment funnel). Users must manually navigate sub-modules to find answers.

**Proposed AI Feature:**

A natural language chat panel embedded in the HRM dashboard that allows HR managers to ask questions like:

> "Who has more than 5 pending leave days this month?"  
> "Which department has the highest absenteeism rate this week?"  
> "List candidates in the 'interviewing' stage for the Senior Developer role."  
> "What is Ahmad's attendance rate for March?"

**Architecture:**

```
User → Chat Input → POST /hrm/ai-assistant/query
                         ↓
               AiHrAssistantService::handle($query, $companyId)
                         ↓
               1. Classify intent (leave / attendance / recruitment / employee)
               2. Query relevant model with company_id scope
               3. Format results as natural language + optional table
                         ↓
               Return JSON { answer: string, data: array|null }
```

This uses Claude with a system prompt that has awareness of company-scoped data descriptions. It does NOT pass raw SQL — it maps intent → safe query → formatted response.

**Business Value:**  
- Dramatically reduces time to find information across all HRM sub-modules.
- Non-technical HR staff can self-serve without learning the interface deeply.

---

### 7. AI-Powered Job Description Generator (Recruitment)

**Where:** `JobController.php` — `create()` and `store()` methods  
**Views:** `jobs/create.blade.php`, `jobs/edit.blade.php`

**Current State:**  
Job postings are written manually. The config has 45 predefined skills that can be selected, but the description, responsibilities, and requirements must be typed from scratch every time.

**Proposed AI Feature:**

On the job creation form, add a **"Generate with AI"** button. The user provides:
- Job title
- Department
- Selected skills (from the existing 45-skill config list)
- Experience level (Junior / Mid / Senior)

Claude generates:
- Full job description
- Responsibilities (bullet list)
- Requirements (bullet list)
- Nice-to-have skills
- Short "About the Role" summary

The generated content is editable before saving.

**Implementation Sketch:**

```javascript
// Frontend (jobs/create.blade.php) — AJAX call on button click
fetch('/hrm/jobs/generate-description', {
    method: 'POST',
    body: JSON.stringify({ title, department, skills, level })
})
.then(res => res.json())
.then(data => {
    document.getElementById('description').value = data.description;
    document.getElementById('requirements').value = data.requirements;
});
```

```php
// New route: POST /hrm/jobs/generate-description
// New method: JobController::generateDescription()
```

**Business Value:**  
- Reduces job posting creation time from 30–45 minutes to under 5 minutes.
- Produces professionally worded, consistent job descriptions.
- Reduces HR dependency on external writing tools.

---

## Implementation Priority Matrix

| # | Feature | Business Value | Implementation Effort | Priority |
|---|---|---|---|---|
| 7 | Job Description Generator | High | Low | **P1 — Start here** |
| 2 | Interview Question Generator | High | Low | **P1** |
| 1 | Resume Parsing & Scoring | Very High | Medium | **P2** |
| 6 | AI HR Assistant Chatbot | Very High | High | **P2** |
| 5 | Leave Recommendation | Medium | Medium | **P3** |
| 4 | Attendance Anomaly Detection | High | Medium | **P3** |
| 3 | Attrition Risk Prediction | Very High | High | **P4** |

---

## Recommended Architecture

### Shared AI Service Layer

Create a single shared service class that all HRM AI features route through. This ensures consistent prompt versioning, error handling, token usage tracking, and retry logic.

```
app/Services/HRM/AI/
├── AiHrmClient.php              ← Shared Claude API wrapper (caching, retries, logging)
├── AiRecruitmentService.php     ← Resume scoring, JD generation, interview questions
├── AiLeaveService.php           ← Leave recommendations, pattern detection
├── AiAttendanceService.php      ← Anomaly detection
├── AiAttritionService.php       ← Turnover risk scoring
└── AiHrAssistantService.php     ← Chatbot intent routing
```

### Model: Claude API Usage

All features should use the **Claude API** (`claude-sonnet-4-6` for production, `claude-haiku-4-5` for high-volume batch jobs like attrition scoring).

Use **prompt caching** for:
- System prompts that include company skill lists or static HR policy text (saves cost on repeated calls).
- The HR Assistant chatbot system prompt (called on every chat message).

```php
// Example: AiHrmClient.php
use Anthropic\Client;

class AiHrmClient
{
    public function complete(array $messages, string $systemPrompt, bool $cache = false): string
    {
        $payload = [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 2048,
            'system'     => $cache
                ? [['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']]]
                : $systemPrompt,
            'messages'   => $messages,
        ];

        return $this->client->messages()->create($payload)->content[0]->text;
    }
}
```

---

## Database Changes Required

```sql
-- hrm_candidates: resume AI fields
ALTER TABLE hrm_candidates
  ADD COLUMN ai_score          SMALLINT,
  ADD COLUMN ai_summary        TEXT,
  ADD COLUMN extracted_skills  JSONB;

-- employees: attrition risk fields
ALTER TABLE employees
  ADD COLUMN attrition_risk_score      SMALLINT,
  ADD COLUMN attrition_risk_level      VARCHAR(10),  -- low/medium/high
  ADD COLUMN attrition_risk_updated_at TIMESTAMP;

-- leave_records: AI recommendation fields
ALTER TABLE leave_records
  ADD COLUMN ai_recommendation        VARCHAR(20),   -- approve/flag/decline
  ADD COLUMN ai_recommendation_reason TEXT;
```

---

## API Keys & Configuration

Add to `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-...
HRM_AI_ENABLED=true
HRM_AI_RESUME_SCORING=true
HRM_AI_LEAVE_ASSIST=true
HRM_AI_ATTRITION=true
HRM_AI_CHATBOT=true
```

Add to `config/hrm.php`:

```php
'ai' => [
    'enabled'          => env('HRM_AI_ENABLED', false),
    'model_default'    => 'claude-sonnet-4-6',
    'model_batch'      => 'claude-haiku-4-5-20251001',
    'features'         => [
        'resume_scoring'   => env('HRM_AI_RESUME_SCORING', false),
        'leave_assist'     => env('HRM_AI_LEAVE_ASSIST', false),
        'attrition'        => env('HRM_AI_ATTRITION', false),
        'chatbot'          => env('HRM_AI_CHATBOT', false),
    ],
],
```

Each feature can be individually toggled without code changes.

---

## What Does NOT Need AI

The following areas are working well with rule-based logic and should **not** have AI added:

| Area | Reason |
|---|---|
| Leave balance calculation | Deterministic math — AI adds no value here |
| Attendance check-in/check-out | Timing logic is precise, rule-based |
| Payroll calculation | Financial accuracy requires deterministic rules, not probabilistic AI |
| Department / Employee Type CRUD | Simple record management |
| PDF generation for announcements | Template-based, no need for AI |
| Fingerprint attendance punch | Hardware integration — no AI role |

---

## Effort Estimate Summary

| Phase | Features Included | Estimated Dev Time |
|---|---|---|
| Phase 1 | Job Description Generator + Interview Questions | 3–4 days |
| Phase 2 | Resume Parsing & Scoring + Shared AI Client | 5–7 days |
| Phase 3 | Leave Recommendation + Attendance Anomaly Detection | 5–6 days |
| Phase 4 | AI HR Assistant Chatbot + Attrition Risk | 8–10 days |
| **Total** | All 7 features | **~21–27 days** |

---

## Summary

The HRM module has rich, well-structured data and clean API boundaries — the ideal foundation for AI integration. The highest ROI starting point is **Phase 1** (Job Description Generator + Interview Question Generator) because both are low-effort, zero-schema-change features that deliver immediate daily value to HR staff. Phase 2 (Resume Scoring) is the most transformational for recruitment quality. The AI HR Assistant Chatbot (Phase 4) has the broadest reach but requires the most careful implementation.

All proposed integrations follow the existing multi-tenant `company_id` scoping pattern and respect the existing permission/middleware architecture.
