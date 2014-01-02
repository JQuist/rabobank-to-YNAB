Rabobank bank statement to YNAB file format.
=================
This script formats the Dutch Rabobank CSV format for bank statements so it can be imported by YouNeedABudget.

Credits and thanks to YellowYellow BV for providing the Rabobank CSV parsing script. (https://github.com/YY-/rabo-to-xero)

YNAB - format: Date,Payee,Category,Memo,Outflow,Inflow.

 * 1. BOEKDATUM -> (Converted to the YNAB format: DD/MM/YY)
 * 2. NAAR_NAAM -> (In the case of Chipknip or Betaalautomaat an appropriate value is added.)
 * 3.           (Empty field to comply with the YNAB CSV format.) 
 * 4. OMSCHR1 -> (All description lines are pasted together into this field.)  
 * 5. BEDRAG -> (In the case of an inflow a second comma is added.)
 
Script output: BOEKDATUM,NAAR_NAAM,,OMSCHR1,BEDRAG

Copyright 2014 JQuist.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
