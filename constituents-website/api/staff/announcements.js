import { db } from "hatchable";
export const access = "admin";
export const methods = ["GET", "POST"];
export default async function (req, res) {
  if (req.method === "GET") {
    const { rows } = await db.query("SELECT id, title, body, category, published, created_at FROM announcements ORDER BY created_at DESC LIMIT 100");
    return res.json({ announcements: rows });
  }
  const title = String(req.body?.title || "").trim();
  const body = String(req.body?.body || "").trim();
  const category = String(req.body?.category || "announcement").trim();
  const published = req.body?.published === true;
  if (!title || !body) return res.status(400).json({ error: "Title and body are required." });
  const { rows } = await db.query("INSERT INTO announcements (title, body, category, published, created_by) VALUES ($1, $2, $3, $4, $5) RETURNING id, title, body, category, published, created_at", [title, body, category, published, req.member.id]);
  res.status(201).json({ announcement: rows[0] });
}