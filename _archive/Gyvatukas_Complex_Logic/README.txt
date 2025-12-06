GYVATUKAS COMPLEX LOGIC - ARCHIVED
===================================

Archive Date: 2025-12-05
Reason: Business decision to simplify Gyvatukas handling

CONTENTS:
---------
This archive contains the complex seasonal calculation logic for Gyvatukas (Heated Towel Rail) 
that was originally implemented with:

1. Seasonal calculations (heating season: October-April, non-heating season: May-September)
2. Winter calculation: Uses stored summer average
3. Summer calculation: Q_circ = Q_total - (V_water × c × ΔT) formula
4. Distribution methods: Equal or by area
5. Configuration-driven parameters

FILES ARCHIVED:
---------------
- GyvatukasCalculator.php (main service)
- GyvatukasCalculatorService.php (alternative implementation)
- GyvatukasCalculatorSecure.php (secure version)
- GyvatukasCalculatorPolicy.php (authorization policy)
- Multiple test files covering all scenarios
- Configuration file (config/gyvatukas.php)
- Language files for translations

REPLACEMENT:
------------
The complex logic has been replaced with a manual entry approach where Gyvatukas is treated 
as a simple flat fee, similar to Internet or Security Service. Landlords manually enter the 
cost per month/bill.

FUTURE USE:
-----------
This logic is preserved for potential future use if the business requirements change back 
to requiring automated seasonal calculations.

For questions, refer to the project documentation or git history around 2025-12-05.
