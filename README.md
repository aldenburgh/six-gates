# Six Gates: Personal Financial Advisor (V6.0)

> **Current Version:** 6.0  
> **Status:** Active Development  
> **Goal:** €20,000 Monthly Dividend Income by 2035

## 📖 Overview

**Six Gates** is an advanced, automated investment analysis system tailored for a long-term **Personal Financial Advisor** workflow. Unlike simple stock screeners, Six Gates acts as a proactive partner in portfolio management, providing **actionable, specific trade recommendations** (Buy/Sell) complete with order types, limit prices, and valid-until dates.

The system is designed to remove emotional decision-making from investing by enforcing a rigid **8-Gate Quality Analysis** framework and a strict **Valuation Discipline**.

## 🎯 The Mission

Transitioning from a passive analyzer to an active advisor, the system operates with one clear objective:

*   **Target:** €20,000 / month in passive dividend income.
*   **Timeline:** 10 Years (2025–2035).
*   **Strategy:** Dual Portfolio Approach (Growth for capital compounding, Dividend for income generation).
*   **Execution:** 100% Manual User Control (The system advises; YOU execute).

---

## 🏗 System Architecture

The project follows a clean **3-Layer Architecture**:

1.  **Frontend (iPad/CLI):** Presentational layer for reviewing recommendations, approving/denying trades, and logging executions.
2.  **Backend API (Service Layer):** Encapsulates extensive business logic for scoring, position sizing, order type advice, and portfolio management.
3.  **Database (MySQL):** The single source of truth for all positions, analysis history, recommendations, and execution logs.

### Core Components

*   **The Six Gates Engine:** A pipeline of 8 strict criteria (ROIC, Moat, Debt, Cash Flow, etc.) that every stock must pass.
*   **Market Context Assessor:** Adjusts risk tolerance and position sizing based on macro factors (VIX, Yield Spreads, CAPE).
*   **Recommendation Engine:** Generates specific advise (e.g., "Buy 658 shares of JNJ at €152.00 (Limit)").
*   **Execution Logger:** Tracks the variance between recommended trades and actual execution to refine future advice.

---

## 🚀 Key Features (V6.0)

### 1. Actionable Recommendations
Instead of a generic "Buy Rating", Six Gates provides a complete trade ticket:
*   **Specific Quantity:** Calculated based on portfolio weight and quality tier.
*   **Order Type:** `LIMIT` for standard trades, `MARKET` for urgent exits.
*   **Price Guidance:** Exact limit price targets based on fair value discount.
*   **Validity:** dynamic expiration dates (3-7 days) based on market volatility.

### 2. Approval Workflow
A disciplined decision-making process:
*   **Approve:** Accepts the recommendation. System waits for execution log.
*   **Deny:** Rejects the trade. User must provide a reason (e.g., "Insufficient Funds", "Market Timing").

### 3. Execution Logging
Closing the loop between advice and reality. After executing a trade at your broker, you log:
*   Actual Shares Bought/Sold
*   Actual Execution Price
*   Commissions
*   *System automatically tracks variance against the recommendation.*

### 4. Portfolio Intelligence
*   **Growth Portfolio:** Focus on "Compounders" (high ROIC, reinvestment).
*   **Dividend Portfolio:** Focus on "Aristocrats" (reliable cash flow).
*   **Rotation Logic:** Automatically suggests rotating profits from Growth -> Dividend when targets are hit.

---

## 🛠 Tech Stack

*   **Language:** PHP 8.2+
*   **Database:** MySQL 8.0
*   **LLM Integration:** Anthropic Claude 3.5 Sonnet (for Qualitative Moat Analysis & Narratives)
*   **Data Source:** Financial Modeling Prep (FMP) Premium API
*   **Infrastructure:** Local/Docker (Designed for self-hosted privacy)

---

## ⚙️ Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/aldenburgh/six-gates.git
    cd six-gates
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Configure Environment**
    Copy `.env.example` to `.env` and populate your keys:
    ```ini
    DB_DATABASE=six_gates
    DB_USERNAME=root
    DB_PASSWORD=your_password
    FMP_API_KEY=your_fmp_key
    ANTHROPIC_API_KEY=your_claude_key
    ```

4.  **Initialize Database**
    Run the V6 migration suite to create the schema (warning: resets DB):
    ```bash
    php bin/migrate.php --fresh
    ```

5.  **Run Analysis**
    ```bash
    php bin/advisor.php
    ```

---

## 📉 The 8 Gates (Analysis Framework)

1.  **Gate 1: The Incumbent Check** (Revenue Growth & Stability)
2.  **Gate 1.5: The Moat Assessment** (LLM-based competitive advantage analysis)
3.  **Gate 2: The Economic Engine** (ROIC > 15%, WACC Spread)
4.  **Gate 2.5: Financial Health** (Debt/EBITDA < 3.0)
5.  **Gate 2.75: Capital Allocation** (Share buybacks vs Dilution)
6.  **Gate 3: Cash Flow King** (FCF Conversion > 80%)
7.  **Gate 3.5: Complexity Check** (Business simplicity score)
8.  **Gate 4: Valuation Discipline** (Fair Value Discount)

---

> **Disclaimer:** Six Gates is a personal project for educational and automated analysis purposes. It is not professional financial advice. Always verify trades with your own research.
