# Six Gates: Personal Financial Advisor (V6.0)

> **Current Version:** 6.0  
> **Status:** Active Development  
> **Goal:** €20,000 Monthly Dividend Income by 2035

## 📖 Overview

**Six Gates** is an advanced, automated investment analysis system tailored for a long-term **Personal Financial Advisor** workflow. Unlike simple stock screeners, Six Gates acts as a proactive partner in portfolio management, providing **actionable, specific trade recommendations** (Buy/Sell) complete with order types, limit prices, and valid-until dates.

The system is designed to remove emotional decision-making from investing by enforcing a rigid **8-Gate Quality Analysis** framework and a strict **Valuation Discipline**.

## 🚀 Key Features (V6.0)

### 1. 🤖 AI Analyst Reports (NEW)
The system now integrates **Anthropic's Claude 3.5 Sonnet** to generate human-readable investment memos.
-   **Narrative Analysis:** Translates complex raw metrics into a clear "Buy/Pass" narrative.
-   **Reasoning:** Explains the "Why" behind the decision, highlighting critical strengths and weaknesses.
-   **Revision Criteria:** Each report includes a specific "Conditions for Revision" section, explicitly listing market or company events that would warrant a re-evaluation.
-   **Context Aware:** Compares current analysis with previous reports to highlight trend changes.

### 2. 🛡 The 8 Gates Framework
A strict filtering pipeline that every stock must pass:
1.  **Gate 1: The Incumbent Check** (Revenue Growth & Stability)
2.  **Gate 1.5: The Moat Assessment** (LLM-based competitive advantage analysis)
3.  **Gate 2: The Economic Engine** (ROIC > 15%, WACC Spread)
4.  **Gate 2.5: Financial Health** (Debt/EBITDA < 3.0)
5.  **Gate 2.75: Capital Allocation** (Share buybacks vs Dilution)
6.  **Gate 3: Cash Integrity** (FCF Conversion > 80%)
7.  **Gate 3.5: Complexity Filter** (Business simplicity score)
8.  **Gate 4: Valuation Discipline** (Fair Value Discount)

### 3. Actionable Recommendations
Six Gates provides a complete trade ticket:
-   **Specific Quantity:** Calculated based on portfolio weight and quality tier.
-   **Order Type:** `LIMIT` for standard trades, `MARKET` for urgent exits.
-   **Price Guidance:** Exact limit price targets based on fair value discount.
-   **Validity:** Dynamic expiration dates (3-7 days) based on market volatility.

### 4. Portfolio Intelligence
-   **Market Context:** Adjusts risk tolerance and position sizing based on macro factors (VIX, Yield Spreads).
-   **Execution Logging:** Tracks the variance between recommended trades and actual execution.

---

## 🛠 Tech Stack

-   **Language:** PHP 8.2+
-   **Database:** MySQL 8.0
-   **AI Core:** Anthropic Claude 3.5 Sonnet (Moat Analysis & Report Generation)
-   **Data Provider:** Financial Modeling Prep (FMP) Premium API
-   **Infrastructure:** Local CLI / Cron

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
    Copy `.env.example` to `.env` and set your API keys:
    ```ini
    DB_DATABASE=six_gates
    DB_USERNAME=root
    DB_PASSWORD=your_password
    
    # Data Providers
    FMP_API_KEY=your_fmp_key
    NEWS_API_KEY=your_news_key (Optional)
    
    # LLM
    ANTHROPIC_API_KEY=your_claude_key
    ANTHROPIC_MODEL=claude-3-sonnet-20240229
    ```

4.  **Initialize Database**
    Run the V6 migration suite to create the schema:
    ```bash
    php bin/migrate.php --fresh
    ```

---

## 💻 Usage

### Quick Analysis (Single Ticker)
Run a full analysis and generate a report for a specific stock:
```bash
php bin/analyze.php --ticker AAPL
```
*Output: Quality Pass/Fail, Gate Scores, and the AI-generated Analyst Report.*

### Full Advisor Mode
Launch the interactive advisor for portfolio management and batch processing:
```bash
php bin/advisor.php
```

---

> **Disclaimer:** Six Gates is a personal project for educational and automated analysis purposes. It is not professional financial advice. Always verify trades with your own research.
