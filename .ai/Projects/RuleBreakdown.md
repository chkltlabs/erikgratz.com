Rules are generated through two primary workflows designed to balance manual oversight with long-term automation:

### 1. Learning from Manual Assignment (Implicit Generation)
This is the most common way rules are created. It happens in-context while a user is categorizing their spending:
- **Trigger:** When a user clicks **"Assign"** on a transaction in the Filament UI.
- **The Step:** After selecting a `Spend` or `PeriodicSpend`, the user is presented with a **"Save as rule for future transactions"** toggle.
- **Pattern Customization:** When toggled, the system pre-fills a **"Matching Pattern"** field with the transaction's `payee` or `description`. The user can refine this (e.g., changing "NETFLIX.COM* 12345" to just "Netflix") to ensure broader matching for future occurrences.
- **Result:** Upon submission, the transaction is assigned and confirmed, and a new `SimpleFinRule` is created in the background.

### 2. Manual Rule Management (Explicit Generation)
For users who want to pre-emptively set up their automation or refine existing logic, there is a dedicated management interface:
- **Resource:** The **"Rules"** section under the **SimpleFIN** navigation group (`SimpleFinRuleResource`).
- **Capability:** Users can manually create rules by defining a substring pattern and associating it with a specific budget item.
- **Refinement:** This interface allows for bulk deletion of obsolete rules or updating patterns if a payee changes their billing description.

### How Generated Rules are Applied
Once generated (via either method), rules are utilized by the `SimpleFinCategorizationService` during:
- **Daily Background Syncs:** Automatically categorizing new incoming data.
- **On-Demand Syncs:** Triggered via the "Sync & Auto-match" header action.
- **Retroactive Processing:** Via the "Re-categorize All" action, which applies newly created rules to existing unconfirmed transactions.

This dual-path approach ensures that the system "learns" from the user's daily activity while still providing full manual control over the logic.
