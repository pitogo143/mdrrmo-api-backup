import { db } from "hatchable";
export const access = "public";
export const methods = ["GET"];
export default async function (req, res) {
  const { rows } = await db.query("SELECT id, title, body, category, created_at FROM announcements WHERE published = true ORDER BY created_at DESC LIMIT 20");
  res.json({ announcements: rows });
}